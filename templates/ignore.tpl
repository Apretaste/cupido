<h1>Resultado de ocultar los perfiles</h1>
<ul>
{foreach item=item from=$ignores}
    <li>{$item['message_before']} {link href="PERFIL {$item['username']}" caption ="@{$item['username']}"} {$item['message_after']}</li>
{/foreach}
</ul>
<p>Los perfiles ocultados no se te mostrar&aacute;n m&aacute;s en las b&uacute;squedas de Cupido.</p>
<center>{button href="CUPIDO" caption="Cupido"}</center>