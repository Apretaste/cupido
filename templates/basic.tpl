{if $random}
<h1>Cinco perfiles al azar</h1>
{else}
<h1>Perfiles que quiz&aacute;s te gusten</h1>
{/if}
{foreach name=matchs item=item from=$matchs}
    <table><tr><td width="100">
    {if ! empty($item->picture)} 
		{img src="{$item->picture}" alt="Picture" width="200"}
	{/if}
    </td><td>
    <p>{link href="PERFIL {$item->username}" caption="{$item->username}"}: {$item->description}</p>
    {if $item->button_like}{button href="CUPIDO LIKE {$item->username}" caption="Me gusta" color="green"}{/if} 
    {button href="CUPIDO OCULTAR {$item->username}" caption="Ocultar" color="red"}
    {button href="NOTA {$item->username} Hola, quisiera conocerte" caption="Enviar nota" color="blue" body="Cambie la nota en el asunto por la que usted desea"}
    </td></tr></table>   
    
    {space5}
{/foreach}
{space10}
<center>
{button href="CUPIDO OCULTAR {foreach name=matchs item=item from=$matchs}{$item->username} {/foreach}" caption="Ocultar todos" color="red"}
{button href="CUPIDO LIKE {foreach name=matchs item=item from=$matchs}{$item->username} {/foreach}" caption="Me gustan todos" color="green"}
</center>