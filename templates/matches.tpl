{if $random}
	<h1>Cinco personas que le pueden interesar</h1>
{else}
	<h1>Personas afines a usted</h1>
{/if}

{space10}

{foreach name=matchs item=item from=$matchs}
	<table width="100%" cellspacing="0" cellspadding="0" border=0>
		<tr>
			<td width="150" valign="top" align="center">
				{if empty($item->picture)}
					{noimage width="150" height="100" text="Tristemente<br/>aun sin foto<br/>:'-("}
				{else} 
					<table cellpadding="3"><tr><td bgcolor="#202020">
					{img src="{$item->thumbnail}" alt="Picture" width="150"}
					</td></tr></table>
				{/if}
			</td>
			<td>&nbsp;&nbsp;</td>
			<td>
 	 			<p>{link href="PERFIL {$item->username}" caption="@{$item->username}"}: {$item->description}</p>
 	 			<div>
					{if $item->button_like}{button href="CUPIDO LIKE {$item->username}" caption="&#10084; Me gusta" color="green" size="small"}{/if} 
					{button href="CUPIDO OCULTAR {$item->username}" caption="&#10008; Ocultar" color="red" size="small"}
					{button href="NOTA {$item->username} Hola @{$item->username}. Me alegro encontrar tu perfil revisando cupido. Pareces una persona interesante y tenemos intereses en comun. Me gustaria llegar a conocerte mejor. Por favor respondeme." caption="Enviar nota" color="grey" body="Cambie la nota en el asunto por la que usted desea" size="small"}
				</div>
			</td>
		</tr>
	</table>   
	{space10}
{/foreach}

{space10}

<center>
	<p><small>Los usuarios que usted oculte nunca se le mostrar&aacute;n nuevamente</small></p>
	{button href="CUPIDO OCULTAR {foreach name=matchs item=item from=$matchs}{$item->username} {/foreach}" caption="&#10008; Ocultar todos" color="red"}
	{button href="NOTA" caption="Ver notas" color="grey"}
</center>

{space30}

<p><small><b>1.</b> Si nuestras sugerencias no le agradan, asegure que {link href="PERFIL EDITAR" caption="su perfil"} est&eacute; correcto y completo.</small></p>
<p><small><b>2.</b> Si ya encontr&oacute; a su media naranja, puede {link href="CUPIDO SALIR" caption="salir de Cupido"} para no mostrar su perfil.</small></p>

