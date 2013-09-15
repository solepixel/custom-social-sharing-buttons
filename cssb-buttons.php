<ul class="cssb-share-counters">
	<li class="cssb-twitter-share"><a href="https://twitter.com/share?url=<?php echo urlencode($link); ?>" target="_blank" title="Tweet" data-dims="675x230">
		<span class="cssb-counter"><?php echo $tweets; ?></span>
		<span class="cssb-button">Tweet</span>
	</a></li>
	<li class="cssb-facebook-share"><a href="http://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($link); ?>" target="_blank" title="Share on Facebook">
		<span class="cssb-counter"><?php echo $fb_likes; ?></span>
		<span class="cssb-button">Like</span>
	</a></li>
	<li class="cssb-google-share"><a href="https://plus.google.com/share?url=<?php echo urlencode($link); ?>" target="_blank" title="Post on Google" data-dims="480x420">
		<span class="cssb-counter"><?php echo $pluses; ?></span>
		<span class="cssb-button">+1</span>
	</a></li>
</ul>
