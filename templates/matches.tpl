{if $noProfilePic}
	<table width="100%" cellpadding="0" cellspacing="0"><tr bgcolor="#F2DEDE">
		<td width="1">&nbsp;</td>
		<td><small><font color="#A94442">No tiene foto de perfil. Usuarios con foto reciben 70% m&aacute;s atenci&oacute;n</font></small></td>
		<td width="1">&nbsp;</td>
		<td align="right" valign="middle">{button href="PERFIL EDITAR" size="small" caption="Agregar foto" body="Envie este email tal y como esta. Recibira como respuesta su perfil en modo de edicion."}</td>
	</tr></table>
	{space10}
{/if}

{if $completion lt 85}
	<table width="100%" cellpadding="0" cellspacing="0"><tr bgcolor="#F2DEDE">
		<td width="1">&nbsp;</td>
		<td><small><font color="#A94442">Solo ha llenado el <b>{$completion|number_format}%</b> de su perfil. Complete su perfil para poder sugerirle personas m&aacute;s afines a usted</font></small></td>
		<td width="1">&nbsp;</td>
		<td align="right" valign="middle">{button href="PERFIL EDITAR" size="small" caption="Completar" body="Envie este email tal y como esta. Recibira como respuesta su perfil en modo de edicion."}</td>
	</tr></table>
	{space10}
{/if}

{if $noProvince}
	<table width="100%" cellpadding="0" cellspacing="0"><tr bgcolor="#F2DEDE">
		<td width="1">&nbsp;</td>
		<td><small><font color="#A94442">Incluya su provincia para poder sugerirle personas cercanas a usted</font></small></td>
		<td width="1">&nbsp;</td>
		<td align="right" valign="middle">{button href="PERFIL EDITAR" size="small" caption="Incluir" body="Envie este email tal y como esta. Recibira como respuesta su perfil en modo de edicion."}</td>
	</tr></table>
	{space10}
{/if}

{if $fewInterests}
	<table width="100%" cellpadding="0" cellspacing="0"><tr bgcolor="#F2DEDE">
		<td width="1">&nbsp;</td>
		<td><small><font color="#A94442">Agregue 10 &oacute; m&aacute;s intereses para poder encontrarle su pareja ideal</font></small></td>
		<td width="1">&nbsp;</td>
		<td align="right" valign="middle">{button href="PERFIL EDITAR" size="small" caption="Agregar" body="Envie este email tal y como esta. Recibira como respuesta su perfil en modo de edicion."}</td>
	</tr></table>
	{space10}
{/if}


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
			<td valign="top">
				{if $item->commonInterests}<small style="background-color:#DFF0D8;"><font color="#3C763D"><nobr>intereses comunes</nobr></font></small>&nbsp;{/if}
				{if $item->province != "" && $item->province == $profile->province}<small style="background-color:#FCF8E3;"><font color="#8A6D65"><nobr>viven cerca</nobr></font></small>&nbsp;{/if}
				{if $item->popular}<small style="background-color:#D9EDF7;"><font color="#757B8F"><nobr>super popular</nobr></font></small>&nbsp;{/if}
				{if $item->religion != "" && $item->religion == $profile->religion}<small style="background-color:#F2DEDE;"><font color="#CF5C42"><nobr>misma creencia</nobr></font></small>&nbsp;{/if}

 	 			<p>{link href="PERFIL {$item->username}" caption="@{$item->username}"}: {$item->description}</p>

 	 			{if $item->button_like}{button href="CUPIDO LIKE @{$item->username}" caption="&hearts; Me gusta" color="green" size="small"}{/if} 
				{button href="CUPIDO OCULTAR @{$item->username}" caption="&#10008; Ocultar" color="red" size="small"}
				{button href="NOTA @{$item->username} Hola @{$item->username}. Me alegro encontrar tu perfil revisando cupido. Pareces una persona interesante y tenemos intereses en comun. Me gustaria llegar a conocerte mejor. Por favor respondeme." caption="Enviar nota" color="grey" body="Cambie la nota en el asunto por la que usted desea" size="small"}
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

