Gracias por votar a favor de los siguientes perfiles. Mandaremos un email a cada uno de los que ahora te gustan,
dej&aacute;ndoles saber que tienen {$admirador}.

<ul>
{foreach item=item from=$likes}
    <li>{if $item['ya']}Ya a ti te gustaba{/if} {link href="PERFIL {$item['username']}" caption="{$item['full_name']}"}</li>
{/foreach}
</ul>

<center>{button href="CUPIDO" caption="Cupido"}</center>