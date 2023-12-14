{if isset($feedContent) && $feedContent}
    <div class="panel">
        <h3><i class="icon icon-rss"></i> Latest Articles from WordPress</h3>
        <ul>
            {foreach from=$feedContent item=article}
                <li><a href="{$article.link}" target="_blank">{$article.title}</a></li>
            {/foreach}
        </ul>
    </div>
{else}
    <div class="panel">
        <h3><i class="icon icon-warning"></i> Error Loading Feed</h3>
        <p>Unable to load the latest articles. Please check the feed URL and try again.</p>
    </div>
{/if}
