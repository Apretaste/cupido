<h1>Resultado de ignorara los perfiles</h1>
<ul>
{foreach item=item from=$ignores}
    <li>{$item['message_before']} {link href="PERFIL {$item['username']}" caption ="{$item['username']}"} {$item['message_after']}</li>
{/foreach}
</ul>

<center>{button href="CUPIDO" caption="Cupido"}</center>