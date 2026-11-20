<?php

/**
 * Tidies the forum's event titles and text for the Events widget, without
 * any AI: titles lose the date and time the widget already shows, and the
 * summary is the post's first real paragraph or two, ending at a sentence.
 *
 * The rules come from the forum's Public Events posts since 2022.
 */
class SLMS_Event_Text {

	// About how long a summary should be, and the most it may be
	const SUMMARY_LENGTH = 140;
	const SUMMARY_MAX    = 240;

	/**
	 * A title without its date and time, such as "Metal Night – Friday 20/11
	 * – Cutting Letters Out of Steel!" or "Open Evening Wed 25 November,
	 * 7-9pm".
	 */
	public static function title( $title ) {
		// Notes from editing the post, rather than part of the title
		$title = preg_replace( '/^\s*edit(ed)?\s*:\s*/i', '', $title );

		// The date and time, wherever they are
		$title = preg_replace( '/' . self::date_run() . '/iu', ' ', $title );

		$title = self::tidy_separators( $title );

		// A word left hanging by the date, as in "Show and Tell meetup in"
		$title = preg_replace( '/\s+(?:in|on|at|from|for|of|the)$/iu', '', $title );

		// Titles written in capitals, in sentence case
		$letters = preg_replace( '/\P{L}/u', '', $title );
		if ( mb_strlen( $letters ) > 3 && preg_match_all( '/\p{Lu}/u', $letters ) / mb_strlen( $letters ) > .7 ) {
			$title = ucfirst( mb_strtolower( $title ) );
		}

		// "it's electronics night!", once "18 of August," has gone
		$title = mb_strtoupper( mb_substr( $title, 0, 1 ) ) . mb_substr( $title, 1 );

		return $title;
	}

	/**
	 * A short summary from the HTML of the event's forum post: its first
	 * paragraphs, without images, link previews, quotes or dates, greetings,
	 * lines that repeat the title or the time, or lists of @mentions.
	 */
	public static function summary_from_html( $html, $title = '' ) {
		if ( ! trim( $html ) ) {
			return '';
		}

		$doc = new DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8"?><body>' . $html . '</body>' );
		libxml_clear_errors();
		$xpath = new DOMXPath( $doc );

		$class = function ( $name ) {
			return "contains(concat(' ', normalize-space(@class), ' '), ' $name ')";
		};

		// Not text: images and their captions, link previews, quotes, polls,
		// the forum's event box and local dates, code and tables
		$remove = $xpath->query( '//img | //svg | //aside | //blockquote | //pre | //table | //iframe'
			. ' | //*[' . $class( 'lightbox-wrapper' ) . ']'
			. ' | //*[' . $class( 'poll' ) . ']'
			. ' | //*[' . $class( 'discourse-post-event' ) . ']'
			. ' | //*[' . $class( 'discourse-local-date' ) . ']' );
		foreach ( iterator_to_array( $remove ) as $node ) {
			$node->parentNode && $node->parentNode->removeChild( $node );
		}

		// @mentions become plain names, so "hosted by @name" still reads,
		// but are marked so that a line of nothing but mentions can be skipped
		foreach ( iterator_to_array( $xpath->query( '//*[' . $class( 'mention' ) . '] | //*[' . $class( 'mention-group' ) . ']' ) ) as $node ) {
			$node->parentNode->replaceChild( $doc->createTextNode( "\x01" . ltrim( $node->textContent, '@' ) . "\x02" ), $node );
		}

		// Line breaks within a paragraph separate lines, read one at a time
		foreach ( iterator_to_array( $doc->getElementsByTagName( 'br' ) ) as $node ) {
			$node->parentNode->replaceChild( $doc->createTextNode( "\n" ), $node );
		}

		// Paragraphs, or list items if there are none: lists don't read well
		// run together, so are only for posts that are nothing but a list
		$nodes = $xpath->query( '//p' );
		if ( ! $nodes->length ) {
			$nodes = $xpath->query( '//li' );
		}

		$parts = array();
		foreach ( $nodes as $node ) {
			foreach ( preg_split( '/\n+/', $node->textContent ) as $line ) {
				$text = self::clean_text( $line );

				if ( self::is_filler( $text, $title ) ) {
					continue;
				}

				// A mention left after a sentence, as in "…until 8pm. @group"
				$text = preg_replace( '/(?<=[.!?])(\s*\x01[^\x02]*\x02)+$/u', '', $text );
				$text = str_replace( array( "\x01", "\x02" ), '', $text );

				// A long line leading into a list, as in "…and how to use the:"
				// ends the summary, as the list won't read as prose
				if ( preg_match( '/:$/', $text ) ) {
					$parts[] = preg_replace( '/\s*:$/', '…', $text );
					break 2;
				}

				$parts[] = $text;

				if ( mb_strlen( implode( ' ', $parts ) ) >= self::SUMMARY_LENGTH ) {
					break 2;
				}
			}

			// A paragraph that leads into a list, as in "…an opportunity to"
			// then "be approved to use…", "practise using…": finished with the
			// list's items, as one sentence, which ends the summary
			$list = $node->nextSibling;
			while ( $list && XML_ELEMENT_NODE !== $list->nodeType ) {
				$list = $list->nextSibling;
			}

			if ( $parts && $list && in_array( $list->nodeName, array( 'ul', 'ol' ), true ) && ! self::ends_well( end( $parts ) ) ) {
				$items = array();
				foreach ( $xpath->query( './li', $list ) as $item ) {
					$items[] = rtrim( str_replace( array( "\x01", "\x02" ), '', self::clean_text( $item->textContent ) ), '.;,' );
				}

				$last  = array_pop( $items );
				$items = $items ? implode( ', ', $items ) . ' and ' . $last : $last;

				$parts[] = array_pop( $parts ) . ' ' . $items . '.';
				break;
			}
		}

		$summary = self::shorten( self::join_sentences( $parts ) );

		// Nothing worth showing, rather than an ellipsis or a word or two
		return preg_match_all( '/\p{L}{2,}/u', $summary ) > 3 ? $summary : '';
	}

	// Lines joined into sentences: a full stop after any without an ending
	private static function join_sentences( $parts ) {
		$text = '';

		foreach ( $parts as $part ) {
			if ( '' !== $text && ! self::ends_well( $text ) && ! preg_match( '/…$/u', $text ) ) {
				$text .= '.';
			}

			$text .= ( '' === $text ? '' : ' ' ) . $part;
		}

		return $text;
	}

	/**
	 * The calendar feed's own description, tidied: for when the forum post
	 * can't be fetched. It is the post's text, flattened and cut short.
	 */
	public static function summary_from_text( $text, $title = '' ) {
		$text = preg_replace( '/@([\w.-]+)/u', "\x01$1\x02", $text );
		// The feed cuts it short mid-word, with an ellipsis
		$text = preg_replace( '/\s*\S*…$/u', '', self::clean_text( $text ) );
		// @mentions it starts with, and the courses' link to the full list
		$text = preg_replace( '/^(\s*\x01[^\x02]*\x02)+\s*/u', '', $text );
		$text = preg_replace( '/^see the complete list of courses\s*/i', '', $text );

		return self::shorten( str_replace( array( "\x01", "\x02" ), '', $text ) );
	}

	/**
	 * Text without what doesn't read as prose: image captions ("photo.jpg
	 * 1024×842 202 KB"), web addresses, the forum's dates and stray spaces.
	 */
	public static function clean_text( $text ) {
		$text = preg_replace( '/\S*\s*\d+×\d+\s+[\d.]+\s*[KMG]B/u', ' ', $text );
		$text = preg_replace( '#https?://\S+#', ' ', $text );
		$text = preg_replace( '/\d{4}-\d\d-\d\dT[\d:]+Z?(\s*→\s*\d{4}-\d\d-\d\dT[\d:]+Z?)?/', ' ', $text );
		// The arrow between two of the forum's dates, once they've gone
		$text = preg_replace( '/\s*→\s*/u', ' ', $text );
		$text = preg_replace( '/\s+/u', ' ', $text );
		$text = preg_replace( '/\s+([,.!?;:])/u', '$1', $text );
		$text = preg_replace( '/,([.!?])/u', '$1', $text );

		return trim( $text );
	}

	/**
	 * Whether a paragraph adds nothing to the listing: empty, a greeting or
	 * sign-off, a lead-in ending in a colon, the title again, a line for the
	 * date, time or place (which the widget shows), boilerplate, or only
	 * @mentions.
	 */
	private static function is_filler( $text, $title ) {
		$words = trim( preg_replace( '/\x01[^\x02]*\x02/', '', $text ), " \t,.!?:;-–" );

		if ( mb_strlen( $words ) < 3 ) {
			return true;
		}

		$short = mb_strlen( $text ) < 50;

		// Little but a date and time, as in "Running on 06.02.25, 19:00 - 21:00"
		$undated = preg_replace( '/' . self::date_run() . '/iu', ' ', $words );
		if ( $undated !== $words && preg_match_all( '/\p{L}{2,}/u', $undated ) <= 2 ) {
			return true;
		}

		return
			// Greetings and sign-offs
			( $short && preg_match( '/^(hi|hello|hey|hiya|dear|greetings|morning|evening all|thanks|thank you|cheers|see you|best|regards|love)\b/i', $words ) )
			// Labels, such as "Messy Room" or "Main Table: name": a few words
			// with no full stop
			|| ( preg_match_all( '/\p{L}{2,}/u', $words ) <= 3 && ! preg_match( '/[.!?]$/u', $words . substr( $text, -1 ) ) )
			// Short lead-ins, such as "Well then come along to our:"
			|| ( mb_strlen( $text ) < 60 && preg_match( '/:$/', $text ) )
			// The date, time, place or host, which the widget shows or links to
			|| preg_match( '/^(date|time|when|where|location|venue|host(ed)?( by)?|running on)\s*:/i', $text )
			// Boilerplate
			|| preg_match( '/^(click here|see the complete|edit\s*:|update\s*:|tagging)/i', $text )
			// The title again
			|| ( $short && self::same_words( $text, $title ) );
	}

	// Whether two bits of text have the same words, ignoring dates and case
	private static function same_words( $a, $b ) {
		$norm = function ( $s ) {
			$s = preg_replace( '/' . self::date_run() . '/iu', ' ', $s );
			return trim( preg_replace( '/[^\p{L}\p{N}]+/u', ' ', mb_strtolower( $s ) ) );
		};

		$a = $norm( $a );
		$b = $norm( $b );

		return $a && $b && ( false !== strpos( $b, $a ) || false !== strpos( $a, $b ) );
	}

	/**
	 * Text cut to whole sentences, about SUMMARY_LENGTH long: at the end of
	 * the sentence that passes it, or before it if that one is too long,
	 * or failing that, at a word, with an ellipsis.
	 */
	private static function shorten( $text ) {
		$text = trim( $text );

		if ( mb_strlen( $text ) <= self::SUMMARY_MAX ) {
			return self::ends_well( $text ) ? $text : self::at_sentence( $text );
		}

		return self::at_sentence( mb_substr( $text, 0, self::SUMMARY_MAX ) );
	}

	// Whether text ends at the end of a sentence
	private static function ends_well( $text ) {
		return (bool) preg_match( '/[.!?)"”’…]$/u', $text );
	}

	// Text up to its last full sentence, or to its last word with an ellipsis
	private static function at_sentence( $text ) {
		if ( preg_match( '/^.*[.!?]+(?=\s|$)/us', $text, $m ) && mb_strlen( $m[0] ) >= 40 ) {
			return $m[0];
		}

		return preg_replace( '/\s+\S*$/u', '', $text ) . '…';
	}

	// Days and months as written in the titles: "Wed", "Thurs", "Friday",
	// "Tonight"; "Nov", "Sept", "November"
	const DAY   = '(?:mon|tue|tues|wed|weds|thu|thur|thurs|fri|sat|sun)(?:day|nesday|sday|urday)?\.?|monday|tuesday|wednesday|thursday|friday|saturday|sunday|tonight|today|tomorrow';
	const MONTH = '(?:jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec)(?:uary|ruary|ch|il|e|y|ust|tember|ember|ober)?\.?';

	/**
	 * A pattern for a run of dates and times, with the words between them:
	 * "Wed 25 November, 7-9pm", "Friday 20/11", "Thurs 15 Oct at 19:00",
	 * "12th of Jan 2023", "Oct 23rd, 18:00 - 21:00", "1pm - late(ish)",
	 * "(24th March 7-11pm)".
	 */
	private static function date_run() {
		$ord   = '\d{1,2}(?:st|nd|rd|th)?';
		// A day with just a number, as in "Friday 16th"; a full date after a
		// day is matched as the date, so it's tried first
		$day   = '(?:' . self::DAY . ')(?:\s+(?:the\s+)?' . $ord . '(?![\d:.\/]|\s+(?:of\s+)?' . self::MONTH . '))?';
		$month = '(?:' . self::MONTH . ')';
		$clock = '\d{1,2}(?:[:.]\d{2})?\s*(?:am|pm)|\d{1,2}[:.]\d{2,3}';
		$time  = '(?:\d{1,2}(?:[:.]\d{2})?\s*(?:-|–|to|until)\s*(?:' . $clock . ')|(?:' . $clock . ')(?:\s*(?:-|–|→|to|until|till)\s*(?:' . $clock . '|late(?:\s*\(ish\))?|\d{1,2}(?!\d)))?)';
		$year  = '(?:19|20)\d{2}';
		$date  = '(?:' . $ord . '(?:\s*[-–]\s*' . $ord . ')?(?:\s+of)?\s+' . $month . '(?:,?\s+(?:' . $year . '|\'?\d{2}(?!\d)))?'
			. '|' . $month . '\s+' . $ord . '(?![\d:.])(?:,?\s+' . $year . ')?'
			. '|' . $month . '\s+' . $year
			. '|\d{1,2}[\/.]\d{1,2}(?:[\/.]\d{2,4})?(?![\d:])'
			. '|' . $year . '(?=\s*[,)]|\s*$))';
		$token = '(?:' . $date . '|' . $day . '|' . $time . ')';
		$end   = '(?![\p{L}\d])';
		$join  = '(?:\s*(?:,|-|–|\/|&|and|at|from|on|of|the|until|till)?\s*)';

		return '\(?\s*(?:\b(?:on|at|from|this|next|every)\s+)?\b' . $token . $end . '(?:' . $join . $token . $end . ')*\s*\)?';
	}

	/**
	 * Separators left without anything on one side once the date has gone,
	 * as in "Metal Night – – Cutting Letters" or "Open Evening ,", and
	 * empty brackets.
	 */
	private static function tidy_separators( $title ) {
		$title = preg_replace( '/\(\s*\)|\[\s*\]|\*\s*\*/u', '', $title );

		$segments = preg_split( '/(\s+[-–|]\s+|\s*[-–|]\s+|\s+[-–|]\s*|:\s+|,\s*)/u', $title, -1, PREG_SPLIT_DELIM_CAPTURE );
		$out = '';

		for ( $i = 0; $i < count( $segments ); $i += 2 ) {
			$segment = trim( $segments[ $i ] );

			if ( ! preg_match( '/[\p{L}\p{N}]/u', $segment ) ) {
				continue;
			}

			// The separator before it, spaced evenly: " – ", ": " or ", "
			$separator = trim( $segments[ $i - 1 ] ?? '' );
			$separator = in_array( $separator, array( ':', ',' ), true ) ? $separator . ' ' : ' ' . $separator . ' ';

			$out .= ( '' === $out ? '' : $separator ) . $segment;
		}

		$out = preg_replace( '/\s+/u', ' ', $out );
		// No space before punctuation left by a removed word ("tonight!")
		$out = preg_replace( '/\s+([!?.])/u', '$1', $out );

		return trim( $out, " \t,-–|:/" );
	}
}
