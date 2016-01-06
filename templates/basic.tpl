{if $random}
<h1>Cinco perfiles al azar</h1>
{else}
<h1>Personas que quiz&aacute;s te gusten</h1>
{/if}
{foreach name=matchs item=item from=$matchs}
    <table><tr><td width="100">
    {if ! empty($item->picture)} 
		{img src="{$item->picture}" alt="Picture" width="200"}
	{/if}
    </td><td>
    <p>{$item->description}</p>
    {link href="PERFIL {$item->email}" caption="{$item->full_name}"} | 
    {link href="CUPIDO LIKE {$item->email}" caption="Me gusta"} | 
    {link href="CUPIDO IGNORAR {$item->email}" caption="Ignorar"}
    </td></tr></table>
    <hr/>
{/foreach}
