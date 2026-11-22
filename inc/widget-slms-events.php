<?php

/**
 * Lists upcoming events from an iCal feed, by default the forum's
 * Public Events calendar.
 */
class slms_events extends WP_Widget {

	const DEFAULT_FEED = SLMS_FORUM_URL . 'c/public-events/l/calendar.ics';

	// How long to keep the feed before fetching it again
	const CACHE_TIME = 15 * MINUTE_IN_SECONDS;

	// How long to keep an event's forum post, for its summary
	const POST_CACHE_TIME = 6 * HOUR_IN_SECONDS;

	public function __construct() {
		parent::__construct( 'slms_events', 'Events', array( 'classname' => 'multi' ) );
	}

	public function widget( $args, $instance ) {
		$feed   = ! empty( $instance['feed'] ) ? $instance['feed'] : self::DEFAULT_FEED;
		$events = $this->pick( $this->get_events( $feed ), ! empty( $instance['events'] ) ? (int) $instance['events'] : 3 );
		// The stylesheet gives the widget a column per event, up to three
		$columns = max( 1, min( 3, count( $events ) ) );
		?>
			<section class="widget widget__posts" style="--posts: <?php echo $columns; ?>">
				<header>
					<?php echo $args['before_title'] . apply_filters( 'widget_title', 'Events' ) . $args['after_title'] ?>
					<?php if ( ! empty( $instance['text'] ) ): ?>
						<p><?php echo nl2br( esc_html( $instance['text'] ) ) ?></p>
					<?php endif ?>
				</header>
				<div class="posts">
				<?php foreach ( $events as $event ) : ?><!--
				 --><article class="post post__event">
						<header>
							<h3 class="h2"><a href="<?php echo esc_url( $event['url'] ) ?>"><?php echo esc_html( $event['title'] ) ?></a></h3>
							<?php foreach ( $event['labels'] as $label ) : ?>
								<span><?php echo esc_html( $label ) ?></span>
							<?php endforeach ?>
							<time datetime="<?php echo esc_attr( $event['start']->format( DATE_W3C ) ) ?>"><?php echo esc_html( wp_date( $event['all_day'] ? 'l, jS F' : 'l, jS F \f\r\o\m H:i', $event['start']->getTimestamp() ) ) ?></time>
						</header>
						<?php if ( $event['description'] ) : ?>
							<p><?php echo esc_html( $event['description'] ) ?></p>
						<?php endif ?>
					</article><!--
				 --><?php endforeach ?>
				</div><!-- .posts -->
			</section>
		<?php
	}

	/**
	 * The soonest $count events, tidied, always including the next open
	 * evening, in place of the last of the others if need be, and only one
	 * open evening. A repeating event, one with the same name as an earlier
	 * one (whatever its capitals), shows only once, at its next date. Events
	 * are tidied (and their topics fetched) only until there are enough.
	 */
	public function pick( $events, $count ) {
		$open_evening = null;
		$others = array();
		$names = array();

		foreach ( $events as $event ) {
			if ( $open_evening && count( $others ) >= $count - 1 ) {
				break;
			}

			$event = $this->tidy( $event );

			if ( ! $this->is_open_evening( $event ) ) {
				$name = mb_strtolower( preg_replace( '/\s+/u', ' ', trim( $event['title'] ) ) );

				if ( ! isset( $names[ $name ] ) ) {
					$others[] = $event;
					$names[ $name ] = true;
				}
			} elseif ( ! $open_evening ) {
				$open_evening = $event;
			}
		}

		if ( ! $open_evening ) {
			return array_slice( $others, 0, $count );
		}

		$picked = array_merge( array_slice( $others, 0, $count - 1 ), array( $open_evening ) );
		usort( $picked, function ( $a, $b ) {
			return $a['start'] <=> $b['start'];
		} );

		return $picked;
	}

	/**
	 * The next open evening in the feed, tidied, or null
	 */
	public function next_open_evening( $feed = self::DEFAULT_FEED ) {
		foreach ( $this->get_events( $feed ) as $event ) {
			$event = $this->tidy( $event );

			if ( $this->is_open_evening( $event ) ) {
				return $event;
			}
		}

		return null;
	}

	/**
	 * Whether an event is an open evening: tagged open-evening, or failing
	 * that (if its topic couldn't be fetched), titled Open Evening
	 */
	public function is_open_evening( $event ) {
		return in_array( 'open-evening', $event['tags'], true ) || ( ! $event['tags'] && preg_match( '/\bopen\s+evening\b/i', $event['title'] ) );
	}

	/**
	 * An event with its title and description tidied (see SLMS_Event_Text):
	 * the date and time out of the title, which the widget shows anyway, and
	 * a summary from the event's forum post, or failing that, the feed's own
	 * shortened description.
	 */
	public function tidy( $event ) {
		$title   = SLMS_Event_Text::title( $event['title'] );
		$topic   = $this->forum_topic( $event['url'] );
		$summary = SLMS_Event_Text::summary_from_html( $topic['html'], $event['title'] );

		return array_merge( $event, array(
			'title'       => $title ? $title : $event['title'],
			'tags'        => $topic['tags'],
			// The topic's tags, in title case (public-event is Public Event), or
			// for an untagged topic, its forum category, such as Courses
			'labels'      => $topic['tags'] ? array_map( function ( $tag ) {
				return ucwords( str_replace( array( '-', '_' ), ' ', $tag ) );
			}, $topic['tags'] ) : array_filter( array( $this->forum_category( $event['url'], $topic['category'] ?? 0 ) ) ),
			'description' => $summary ? $summary : SLMS_Event_Text::summary_from_text( $event['description'], $event['title'] ),
		) );
	}

	/**
	 * The name of a forum category, from the forum the address is on and the
	 * category's number. Cached for a day, or briefly if the fetch fails.
	 */
	public function forum_category( $url, $id ) {
		if ( ! $id || ! preg_match( '#^https?://[^/]+#', $url, $m ) ) {
			return '';
		}

		$key  = 'slms_event_category_' . md5( $m[0] . $id );
		$name = get_transient( $key );

		if ( false === $name ) {
			$json = $this->fetch_json( $m[0] . '/c/' . (int) $id . '/show.json' );
			$name = (string) ( $json['category']['name'] ?? '' );

			set_transient( $key, $name, $name ? DAY_IN_SECONDS : 15 * MINUTE_IN_SECONDS );
		}

		return $name;
	}

	/**
	 * A forum topic's tags, category and the HTML of its first post, from its address,
	 * such as https://discourse.example.org/t/open-evening/53852. Cached; a
	 * failed fetch is cached briefly, as an empty topic.
	 */
	public function forum_topic( $url ) {
		$empty = array( 'html' => '', 'category' => 0, 'tags' => array() );

		if ( ! preg_match( '#^(https?://[^/]+)/t/(?:[^/]+/)?(\d+)#', $url, $m ) ) {
			return $empty;
		}

		$key   = 'slms_event_topic_' . md5( $m[1] . $m[2] );
		$topic = get_transient( $key );

		if ( false === $topic ) {
			$json = $this->fetch_json( $m[1] . '/t/' . $m[2] . '.json' );
			$topic = $json ? array(
				'html'     => $json['post_stream']['posts'][0]['cooked'] ?? '',
				'category' => (int) ( $json['category_id'] ?? 0 ),
				// Names, or in newer versions of Discourse, objects with a name
				'tags' => array_map( function ( $tag ) {
					return is_array( $tag ) ? $tag['name'] : $tag;
				}, $json['tags'] ?? array() ),
			) : $empty;

			set_transient( $key, $topic, $json ? self::POST_CACHE_TIME : 15 * MINUTE_IN_SECONDS );
		}

		return $topic;
	}

	/**
	 * Upcoming events from the feed, soonest first. The feed is cached; a
	 * failed fetch is cached briefly too, so a forum outage doesn't slow
	 * every page load.
	 */
	public function get_events( $feed ) {
		$key = 'slms_events_' . md5( $feed );
		$ics = get_transient( $key );

		if ( false === $ics ) {
			$body = $this->fetch( $feed );
			$ics  = $body ?? '';

			set_transient( $key, $ics, null !== $body ? self::CACHE_TIME : 5 * MINUTE_IN_SECONDS );
		}

		$now = time();
		$events = array_filter( $this->parse_ics( $ics ), function ( $event ) use ( $now ) {
			return $event['end']->getTimestamp() >= $now;
		} );

		usort( $events, function ( $a, $b ) {
			return $a['start'] <=> $b['start'];
		} );

		return $events;
	}

	/**
	 * Reads the VEVENTs in an iCal file: title, link, start, end and the
	 * description text.
	 */
	/**
	 * A URL's body, or null if it couldn't be fetched
	 */
	private function fetch( $url ) {
		$response = wp_remote_get( $url, array( 'timeout' => 5 ) );

		return ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ? wp_remote_retrieve_body( $response ) : null;
	}

	/**
	 * A URL's JSON, decoded, or null
	 */
	private function fetch_json( $url ) {
		$body = $this->fetch( $url );

		return null === $body ? null : json_decode( $body, true );
	}

	public function parse_ics( $ics ) {
		// Long lines are folded onto following lines that start with a space
		$lines = explode( "\n", preg_replace( "/\r?\n[ \t]/", '', str_replace( "\r\n", "\n", $ics ) ) );
		$events = array();
		$event = null;

		foreach ( $lines as $line ) {
			if ( 'BEGIN:VEVENT' === $line ) {
				$event = array();
			} elseif ( 'END:VEVENT' === $line && is_array( $event ) ) {
				// Skip an event whose date or time zone can't be read, rather
				// than failing the page
				try {
					if ( isset( $event['DTSTART'] ) ) {
						$events[] = $this->to_event( $event );
					}
				} catch ( Exception $e ) {
				}
				$event = null;
			} elseif ( is_array( $event ) && preg_match( '/^([A-Z-]+)((?:;[^:]*)?):(.*)$/', $line, $m ) ) {
				$event[ $m[1] ] = array( 'params' => $m[2], 'value' => $m[3] );
			}
		}

		return $events;
	}

	private function to_event( $raw ) {
		$text = function ( $name ) use ( $raw ) {
			if ( ! isset( $raw[ $name ] ) ) {
				return '';
			}

			$value = strtr( $raw[ $name ]['value'], array( '\\n' => "\n", '\\N' => "\n", '\\,' => ',', '\\;' => ';', '\\\\' => '\\' ) );

			return trim( html_entity_decode( $value, ENT_QUOTES, 'UTF-8' ) );
		};

		$start = $this->to_date( $raw['DTSTART'] );
		$url = $text( 'URL' );

		// The forum starts each description with the topic's address
		$description = trim( preg_replace( '/^' . preg_quote( $url, '/' ) . '/', '', $text( 'DESCRIPTION' ) ) );

		return array(
			'title'       => $text( 'SUMMARY' ),
			'url'         => $url,
			'start'       => $start,
			'end'         => isset( $raw['DTEND'] ) ? $this->to_date( $raw['DTEND'] ) : $start,
			'all_day'     => false !== strpos( $raw['DTSTART']['params'], 'VALUE=DATE' ),
			'description' => preg_replace( '/\s+/', ' ', $description ),
		);
	}

	private function to_date( $prop ) {
		$value = $prop['value'];

		if ( 'Z' === substr( $value, -1 ) ) {
			$zone = new DateTimeZone( 'UTC' );
		} elseif ( preg_match( '/TZID=([^;]+)/', $prop['params'], $m ) ) {
			$zone = new DateTimeZone( $m[1] );
		} else {
			$zone = wp_timezone();
		}

		return new DateTimeImmutable( rtrim( $value, 'Z' ), $zone );
	}

	public function form( $instance ) {
		$feed = ! empty( $instance['feed'] ) ? $instance['feed'] : self::DEFAULT_FEED;
		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'events' ) ?>">Events:</label>
				<input type="number" min="1" max="12" id="<?php echo $this->get_field_id( 'events' ) ?>" name="<?php echo $this->get_field_name( 'events' ) ?>" value="<?php echo esc_attr( $instance['events'] ?? 3 ) ?>">
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'feed' ) ?>">Calendar feed (iCal address):</label><br />
				<input type="url" class="widefat" id="<?php echo $this->get_field_id( 'feed' ) ?>" name="<?php echo $this->get_field_name( 'feed' ) ?>" value="<?php echo esc_attr( $feed ) ?>">
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'text' ) ?>">Text:</label><br />
				<textarea id="<?php echo $this->get_field_id( 'text' ) ?>" name="<?php echo $this->get_field_name( 'text' ) ?>"><?php echo esc_textarea( $instance['text'] ?? '' ) ?></textarea>
			</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['text'] = ( ! empty( $new_instance['text'] ) ) ? strip_tags( $new_instance['text'] ) : '';
		$instance['feed'] = ( ! empty( $new_instance['feed'] ) ) ? esc_url_raw( $new_instance['feed'] ) : '';

		$instance['events'] = 3;

		if ( ! empty( $new_instance['events'] ) && is_numeric( $new_instance['events'] ) && $new_instance['events'] >= 1 && $new_instance['events'] <= 12 )
			$instance['events'] = $new_instance['events'];

		return $instance;
	}
}
