<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:newznab="http://www.newznab.com/DTD/2010/feeds/attributes/" encoding="utf-8">
    <channel>
        <atom:link href="{$serverroot}api" rel="self" type="application/rss+xml" />
        <title>{$title|escape}</title>
        <description>{$title|escape} API Results</description>
        <link>{$serverroot}</link>
        <language>de-de</language>
        <newznab:response offset="0" total="{if $releases|@count > 0}{$releases|@count}{else}0{/if}" />
        {if $releases}
        {foreach from=$releases item=release}
        <item>
            <title>{$release.searchname|escape:html}</title>
            <id>{$release.guid}</id>
            <guid isPermaLink="true">{$serverroot}details/{$release.guid}</guid>
            <link>{$serverroot}api?t=get&amp;guid={$release.guid}&amp;apikey={$rsstoken}</link>
            <comments>http://nzb.to/index.php?p=nzb&amp;nid={$release.guid}</comments>
            <pubDate>{$release.adddate}</pubDate>
            <category>{$release.category_name|escape:html}</category>
            <description>{$release.searchname|escape:html}</description>
            <enclosure url="{$serverroot}api?t=get&amp;guid={$release.guid}&amp;apikey={$rsstoken}" length="{$release.size}" type="application/x-nzb" />
            <newznab:attr name="size" value="{$release.size}" />
        {if $extended=="1"}
            <newznab:attr name="files" value="{$release.totalpart}" />
            <newznab:attr name="poster" value="{$release.fromname|escape:html}" />
            {if $release.season != ""}
            <newznab:attr name="season" value="{$release.season}" />
        {/if}
        {if $release.episode != ""}
            <newznab:attr name="episode" value="{$release.episode}" />
        {/if}
        {if $release.category != ""}
            <newznab:attr name="category" value="{$release.category}" />
        {/if}
        {if $release.rageID != "-1" && $release.rageID != "-2"}
            <newznab:attr name="rageid" value="{$release.rageID}" />
        {/if}
        {if $release.tvtitle != ""}
            <newznab:attr name="tvtitle" value="{$release.tvtitle|escape:html}" />
        {/if}
        {if $release.tvairdate != ""}
            <newznab:attr name="tvairdate" value="{$release.tvairdate}" />
        {/if}
        {if $release.imdbID != ""}
            <newznab:attr name="imdb" value="{$release.imdbID}" />
        {/if}
            <newznab:attr name="grabs" value="{$release.grabs}" />
            <newznab:attr name="comments" value="{$release.comments}" />
            <newznab:attr name="password" value="{$release.passwordstatus}" />
            <newznab:attr name="usenetdate" value="{$release.postdate}" />
            <newznab:attr name="group" value="{$release.group_name|escape:html}" />
        {/if}
        </item>
        {/foreach}
        {/if}
    </channel>
</rss>
