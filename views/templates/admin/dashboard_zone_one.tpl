<div class="panel">
    <h3><i class="icon icon-credit-card"></i> {l s='Latest Articles' mod='cofisnews'}</h3>
    <ul>
        {foreach from=$feedContent item=article}
            <li><a href="{$article.link}" target="_blank">{$article.title}</a></li>
        {/foreach}
    </ul>
</div>
