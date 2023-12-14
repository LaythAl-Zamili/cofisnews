{if isset($feedContent) && $feedContent}
    <ul>
        {foreach from=$feedContent key=articleIndex item=article}
            <li>
                <a href="{$article.link}" target="_blank" rel="noopener">
                    {$article.title}
                </a>
            </li>
        {/foreach}
    </ul>
{else}
    <p>No articles available.</p>
{/if}
