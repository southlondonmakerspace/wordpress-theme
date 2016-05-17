South London Makerspace
=======================

### Development

```
npm install
gulp watch
```

### Deployment

Any changes committed to the `master` branch will be automatically uploaded to the development preview at [slms.wpengine.com](http://slms.wpengine.com)

### Discourse Comments

As discourse is the primary forum for discussions we use their [wp-discourse](https://github.com/discourse/wp-discourse) plugin to sync comments through from the Blog category. These are settings we've got within WordPress.

```html
<li class="comment even thread-even depth-1">
  <article class="comment-body">
    <footer class="comment-meta">
      <div class="comment-author vcard">
        <img alt="" src="{avatar_url}" class="avatar avatar-64 photo avatar-default" height="45" width="45">
        <b class="fn"><a href="{topic_url}" rel="external" class="url">{username}</a></b>
      <div class="comment-metadata">
        <time pubdate="" datetime="{comment_created_at}">{comment_created_at}</time>
      </div><!-- .comment-metadata -->

      </div><!-- .comment-author -->
    </footer><!-- .comment-meta -->
    <div class="comment-content">{comment_body}</div><!-- .comment-content -->
  </article><!-- .comment-body -->
</li>
```
