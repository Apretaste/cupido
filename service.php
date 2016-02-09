<?php

/**
 * Apretaste
 * 
 * Cupido Service
 * 
 * @version 1.0
 */
class Cupido extends Service
{

	private $db = null;

	/**
	 * Function executed when the service is called
	 *
	 * @param Request $request			
	 * @return Response
	 *
	 */
	public function _main (Request $request)
	{
		$user = $this->utils->getPerson($request->email);
		$completion = $this->utils->getProfileCompletion($request->email);
		
		// Verifying profile completion
		if ($completion * 1 < 70 || empty($user->gender) || empty($user->full_name))
		{
			$response = new Response();
			$response->setResponseSubject("Cree su perfil en Apretaste!");
			$response->createFromTemplate('not_profile.tpl', array('email' => $request->email));
			return $response;
		}
		
		// Activating person in cupido
		$this->db()->deepQuery("UPDATE person SET cupido = 1 WHERE email = '{$request->email}';");
		
		// Match algorithm - building sql query
		$common_filter = " person.email <> '{$request->email}' AND cupido = 1 AND NOT EXISTS (SELECT * FROM _cupido_ignores WHERE _cupido_ignores.email2 = person.email) ";
		if ($user->sexual_orientation == 'HETERO') $common_filter .= " AND person.gender <> '{$user->gender}' AND person.sexual_orientation <> 'HOMO'";
		if ($user->sexual_orientation == 'HOMO') $common_filter .= " AND person.gender = '{$user->gender}' AND person.sexual_orientation <> 'HETERO'";
		if ($user->sexual_orientation == 'BI') $common_filter .= " AND (person.sexual_orientation = 'BI' OR (person.sexual_orientation = 'HOMO' AND person.gender = '{$user->gender}') OR (person.sexual_orientation = 'HETERO' AND person.gender <> '{$user->gender}'))";
		$common_filter .= " AND person.marital_status = 'SOLTERO' \n";
		$common_filter .= " AND person.cupido = 1\n";
		$common_filter .= " AND abs(datediff(person.date_of_birth, '{$user->date_of_birth}')) / 365 <= 20";
		
		$sql = '';
		if (is_array($user->interests)) {
			// Interests
			$sql = "SELECT email, sum(v) as vv FROM(\n";
			$j = 0;
			$ints = strtolower(implode(",", $user->interests));
			for ($i = 1; $i <= 10; $i ++) {
				$j ++;
				if ($j > 1) $sql .= ' UNION ALL ';
				$sql .= " select email, IF('{$ints}' LIKE CONCAT('%',lower(split_str(interests,',',{$i})),'%'),1,0) as v from person WHERE CONCAT('%',lower(split_str(interests,',',{$i})),'%') <> '%%' \n";
			}
			$sql .= ") as subq GROUP BY email\n";
		}

		$subsql = $sql;
		$total_interests = count($user->interests);
		
		$sql = "SELECT email,\n";
		$sql .= "(select proximity FROM ( SELECT  `_province_distance`.`province1` AS `province1`,
			`_province_distance`.`province2` AS `province2`,
			`_province_distance`.`distance` AS `distance`,
			(select (100 - ((`_province_distance`.`distance` / (select max(`dist2`.`distance`) FROM `_province_distance` `dist2`)) * 100))) AS `proximity` 
			FROM `_province_distance`) as _province_proximity WHERE (province1 = person.province AND province2 = '{$user->province}') OR (province2 = person.province AND province1 = '{$user->province}')) / 100 * 15 as percent_proximity,\n";
		$sql .= "(select count(*) FROM _cupido_likes WHERE _cupido_likes.email2 = person.email) / (select count(*) FROM person as __cupido_members WHERE __cupido_members.cupido = 1)  * 15 as percent_likes,\n";
		$sql .= "(select person.skin = '{$user->skin}') * 5 as same_skin,\n" . "(select person.picture is not null) * 20 as having_picture,\n";
		$sql .= "(1 - (abs(datediff(person.date_of_birth, '{$user->date_of_birth}'))/365 / 20)) * 15 as age_proximity,\n";
		$sql .= "(select person.body_type = '{$user->body_type}')* 5 as same_body_type,\n";
		$sql .= "(select person.religion = '{$user->religion}')* 20 as same_religion,\n";
		
		if ($total_interests > 0)
			$sql .= "(select vv FROM ($subsql) as subq1 WHERE email = person.email) / $total_interests * 25 as percent_preferences\n";
		else
			$sql .= "0 as percent_preferences\n";
		$sql .= " FROM person\n WHERE $common_filter \n";
		$subsql = $sql;
		$sql = " SELECT email, percent_proximity + percent_likes + same_skin + having_picture + age_proximity + same_body_type + same_religion + percent_preferences as percent_match\n";
		$sql .= "FROM ($subsql) as subq2\n";
		$sql .= "ORDER BY percent_match DESC, email ASC\n";
		$sql .= "LIMIT 3;\n";
		
		// Executing the query
		$list = $this->db()->deepQuery(trim($sql));
		
		$matchs = array();
		$images = array();
		$random = false;
		
		// If not matchs, return random profiles
		if (empty($list) || is_null($list)) {
			$sql = "SELECT email FROM person WHERE $common_filter ORDER BY rand() LIMIT 3";
			$list = $this->db()->deepQuery($sql);
			$random = true;
		}

		// Proccesing result
		if (is_array($list))
		{
			foreach ($list as $item)
			{
				$profile = $this->utils->getPerson($item->email);
				if ($profile === false) continue;
				if( ! empty($profile->thumbnail)) $images[] = $profile->thumbnail;
				if ($profile->full_name == '') $profile->full_name = $profile->email;
				$profile->button_like = true;
				if ($this->isLike($request->email, $profile->email)) $profile->button_like = false;
				$profile->description = $this->getProfileDescription($profile);

				$matchs[] = $profile;
			}
		}

		// Not found :(
		if ( ! isset($list[0]))
		{
			$response = new Response();
			$response->setResponseSubject('No encontramos perfiles para ti');
			$response->createFromText('No encontramos perfiles para ti');
			return $response;
		}

		// Building response
		$response = new Response();
		if ($random) $response->setResponseSubject('No encontramos perfiles para ti, te mostramos algunos aleatorios');
		else $response->setResponseSubject('Personas de tu interes');
		$response->createFromTemplate('matches.tpl', array('matchs'=>$matchs, 'random'=>$random), $images);
		return $response;
	}

	/**
	 * Subservice SALIR
	 */
	public function _salir (Request $request)
	{
		if ( ! $this->isMember($request->email)) {
			return $this->getNotMemberResponse();
		}
		
		$this->db()->deepQuery("UPDATE person SET cupido = 0 WHERE email = '{$request->email}';");
		
		$response = new Response();
		$response->setResponseSubject('Haz salido de la red de Cupido en Apretaste');
		$response->createFromText('Haz salido de la red de Cupido en Apretaste. Si deseas volver, simplemente usa el servicio.');
		return $response;
	}

	/**
	 * Subservice LIKE
	 *
	 * @param Request $request			
	 * @return Reponse/Array
	 */
	public function _like (Request $request)
	{
		// check if you are a member
		if ( ! $this->isMember($request->email)) {
			return $this->getNotMemberResponse();
		}
		
		if (empty(trim($request->query))) {
			$response = new Response();
			$response->setResponseSubject('No indicaste el nombre de usuario que te gusta');
			$response->createFromText('No indicaste el nombre de usuario que te gusta. Para hacerlo debes escribir el nombre de usuario en el asunto seguido del texto CUIPIDO LIKE.');
			return $response;
		}
		
		// get current user
		$currentUser = $this->utils->getPerson($request->email);
		
		// get caption depending of the gender
		$admirador_caption = 'un(a) admirador(a)';
		if ($currentUser->gender = 'F') $admirador_caption = 'una admiradora';
		if ($currentUser->gender = 'M') $admirador_caption = 'un admirador';
		
		$emails = $this->getEmailsFromRequest($request);
		
		if ( ! isset($emails[0])) {
			$response = new Response();
			$response->setResponseSubject('El nombre de usuario que te gusta no existe en Apretaste.');
			$response->createFromText('Indicaste un nombre de usuario que no existe en Apretaste. Para hacerlo debes escribir el nombre de usuario en el asunto seguido del texto CUIPIDO LIKE.');
			return $response;
		}
		
		$likes = array();
		$email = $emails[0];
		
		if ( ! $this->isMember($email)) return $this->getNotMemberResponse($email);
		
		// get the person whom you hit like
		$person = $this->utils->getPerson($email);
		
		if ($this->isLike($request->email, $email)) {
			$like = array(
					'full_name' => $person->full_name,
					'username' => $person->username,
					'ya' => true
			);
		} else {
			
			$sql = "INSERT INTO _cupido_likes (email1, email2) VALUES ('{$request->email}','$email');";
			$this->db()->deepQuery($sql);
			
			if (empty($person->full_name)) $person->full_name = $person->username;
			
			$like = array(
					'full_name' => $person->full_name,
					'username' => $person->username,
					'ya' => false
			);
		}
		
		$user = $this->utils->getPerson($request->email);
		
		$response2 = new Response();
		$response2->setResponseEmail($email);
		$response2->setResponseSubject('Tienes ' . $admirador_caption);
		$response2->createFromTemplate("like_you.tpl", array(
				'user' => $user
		));
		
		$response1 = new Response();
		$response1->setResponseSubject('Haz indicado que te gusta @' . $person->username);
		
		if (empty($user->full_name)) $user->full_name = $user->username;
		
		$response1->createFromTemplate('like.tpl', array(
				'like' => $like,
				'admirador' => $admirador_caption
		));
		
		return array(
				$response1,
				$response2
		);
	}

	/**
	 * Subservice OCULTAR
	 *
	 * @param Request $request			
	 * @return Response
	 */
	public function _ocultar (Request $request)
	{
		if ( ! $this->isMember($request->email)) return $this->getNotMemberResponse();
		
		$emails = $this->getEmailsFromRequest($request);
		$ignores = array();
		
		if ( ! isset($emails[0])) {
			$response = new Response();
			$response->setResponseSubject("Te falta especificar el perfil a ocultar");
			$response->createFromText("No escribiste en el asunto los nombres de usuarios que deseas ocultar. Por ejemplo: CUPIDO OCULTAR pepe1");
			return $response;
		}
		
		foreach ($emails as $email) {
			
			$person = $this->utils->getPerson($email);
			
			if ($email == $request->email) {
				$ignores[] = array(
						'username' => false,
						'message_before' => 'No puedes ocultarate a ti mismo.',
						'message_after' => 'Verifica que la direcci&oacute;n de correo que escribiste sea la correcta.'
				);
				continue;
			}
			
			if ( ! $this->isMember($email)) {
				$un = $email;
				
				if (is_object($person)) $un = $person->username;
				
				$ignores[] = array(
						'username' => $un,
						'message_before' => '',
						'message_after' => ' no es miembro de la red de cupido en Apretaste.'
				);
				continue;
			}
			
			if ($this->isIgnore($request->email, $email)) {
				$ignores[] = array(
						'username' => $person->username,
						'message_before' => 'A ',
						'message_after' => ' ya lo hab&iacute;as ocultado.'
				);
				continue;
			}
			
			$sql = "INSERT INTO _cupido_ignores (email1, email2) VALUES ('{$request->email}','$email');";
			$this->db()->deepQuery($sql);
			
			$ignores[] = array(
					'username' => $person->username,
					'message_before' => 'Ocultaste satisfactoriamente a ',
					'message_after' => ''
			);
		}
		
		$response = new Response();
		$response->setResponseSubject('Haz ocultado los siguientes perfiles');
		$response->createFromTemplate('ignore.tpl', array(
				'ignores' => $ignores
		));
		
		return $response;
	}

	/**
	 * Return true if $email is member of the network cupido
	 *
	 * @param String $email			
	 * @return Boolean
	 */
	private function isMember ($email)
	{
		$sql = "SELECT cupido FROM person WHERE email = '$email' AND cupido = 1";
		$find = $this->db()->deepQuery($sql);
		
		if (isset($find[0])) return true;
		return false;
	}

	/**
	 * Return a list of emails from request query
	 *
	 * @param Request $request			
	 * @return array
	 */
	private function getEmailsFromRequest ($request)
	{
		$query = explode(" ", $request->query);
		$parts = array();
		
		foreach ($query as $q) {
			$part = trim($q);
			if ($part !== '') $parts[] = $part;
		}
		
		$emails = array();
		foreach ($parts as $part) {
			$find = $this->db()->deepQuery("SELECT email FROM person WHERE username = '{$part}';");
			if (isset($find[0]))
				$emails[] = $find[0]->email;
			else
				$emails[] = $part;
		}
		
		return $emails;
	}

	/**
	 * Search if email1 like email2
	 */
	private function isLike ($email1, $email2)
	{
		$sql = "SELECT * FROM _cupido_likes WHERE email1 = '$email1' AND email2 = '$email2';";
		$find = $this->db()->deepQuery($sql);
		
		if (isset($find[0])) if ($find[0]->email1 == $email1 && $find[0]->email2 == $email2) return true;
		
		return false;
	}

	/**
	 * Search if email1 ignore email2
	 */
	private function isIgnore ($email1, $email2)
	{
		$sql = "SELECT * FROM _cupido_ignores WHERE email1 = '$email1' AND email2 = '$email2';";
		$find = $this->db()->deepQuery($sql);
		
		if (isset($find[0])) if ($find[0]->email1 == $email1 && $find[0]->email2 == $email2) return true;
		
		return false;
	}

	/**
	 * Return common not member response
	 *
	 * @param string $email			
	 * @return Response
	 */
	private function getNotMemberResponse ($email = null)
	{
		$response = new Response();
		
		if (is_null($email)) {
			$response->setResponseSubject("Usted no forma parte la red Cupido");
			$response->createFromText("Usted no forma parte la red Cupido");
		} else {
			$response->setResponseSubject("$email no forma parte la red Cupido");
			$response->createFromText("El usuario $email no forma parte la red Cupido");
		}
		
		return $response;
	}

	/**
	 * Return description of profile as a paragraph
	 *
	 * @param Object $profile			
	 * @return string
	 */
	private function getProfileDescription ($profile)
	{
		
		// get the full name, or the email
		$fullName = empty($profile->full_name) ? $profile->username : $profile->full_name;
		
		// get the age
		$age = empty($profile->date_of_birth) ? "" : date_diff(date_create($profile->date_of_birth), date_create('today'))->y;
		
		// get the gender
		$gender = "";
		if ($profile->gender == "M") $gender = "hombre";
		if ($profile->gender == "F") $gender = "mujer";
		
		// get the final vowel based on the gender
		$genderFinalVowel = "o";
		if ($profile->gender == "F") $genderFinalVowel = "a";
		
		// get the eye color
		$eyes = "";
		if ($profile->eyes == "NEGRO") $eyes = "negro";
		if ($profile->eyes == "CARMELITA") $eyes = "carmelita";
		if ($profile->eyes == "AZUL") $eyes = "azul";
		if ($profile->eyes == "VERDE") $eyes = "verde";
		if ($profile->eyes == "AVELLANA") $eyes = "avellana";
		
		// get the eye tone
		$eyesTone = "";
		if ($profile->eyes == "NEGRO" || $profile->eyes == "CARMELITA" || $profile->eyes == "AVELLANA") $eyesTone = "oscuros";
		if ($profile->eyes == "AZUL" || $profile->eyes == "VERDE") $eyesTone = "claros";
		
		// get the skin color
		$skin = "";
		if ($profile->skin == "NEGRO") $skin = "negr$genderFinalVowel";
		if ($profile->skin == "BLANCO") $skin = "blanc$genderFinalVowel";
		if ($profile->skin == "MESTIZO") $skin = "mestiz$genderFinalVowel";
		
		// get the type of body
		$bodyType = "";
		if ($profile->body_type == "DELGADO") $bodyType = "soy flac$genderFinalVowel";
		if ($profile->body_type == "MEDIO") $bodyType = "no soy de flac$genderFinalVowel ni grues$genderFinalVowel";
		if ($profile->body_type == "EXTRA") $bodyType = "tengo unas libritas de m&aacute;s";
		if ($profile->body_type == "ATLETICO") $bodyType = "tengo un cuerpazo atl&eacute;tico";
		
		// get the hair color
		$hair = "";
		if ($profile->hair == "TRIGUENO") $hair = "trigue&ntilde;o";
		if ($profile->hair == "CASTANO") $hair = "casta&ntilde;o";
		if ($profile->hair == "RUBIO") $hair = "rubio";
		if ($profile->hair == "NEGRO") $hair = "negro";
		if ($profile->hair == "ROJO") $hair = "rojizo";
		if ($profile->hair == "BLANCO") $hair = "canoso";
		
		// get the place where the person live
		$province = "";
		if ($profile->province == "PINAR_DEL_RIO") $province = "Pinar del R&iacute;o";
		if ($profile->province == "LA_HABANA") $province = "La Habana";
		if ($profile->province == "ARTEMISA") $province = "Artemisa";
		if ($profile->province == "MAYABEQUE") $province = "Mayabeque";
		if ($profile->province == "MATANZAS") $province = "Matanzas";
		if ($profile->province == "VILLA_CLARA") $province = "Villa Clara";
		if ($profile->province == "CIENFUEGOS") $province = "Cienfuegos";
		if ($profile->province == "SANCTI_SPIRITUS") $province = "Sancti Sp&iacute;ritus";
		if ($profile->province == "CIEGO_DE_AVILA") $province = "Ciego de &Aacute;vila";
		if ($profile->province == "CAMAGUEY") $province = "Camaguey";
		if ($profile->province == "LAS_TUNAS") $province = "Las Tunas";
		if ($profile->province == "HOLGUIN") $province = "Holgu&iacute;n";
		if ($profile->province == "GRANMA") $province = "Granma";
		if ($profile->province == "SANTIAGO_DE_CUBA") $province = "Santiago de Cuba";
		if ($profile->province == "GUANTANAMO") $province = "Guant&aacute;namo";
		if ($profile->province == "ISLA_DA_LA_JUVENTUD") $province = "Isla de la Juventud";
		
		// get highest educational level
		$education = "";
		if ($profile->highest_school_level == "PRIMARIO") $education = "tengo sexto grado";
		if ($profile->highest_school_level == "SECUNDARIO") $education = "soy graduad$genderFinalVowel de la secundaria";
		if ($profile->highest_school_level == "TECNICO") $education = "soy t&acute;cnico medio";
		if ($profile->highest_school_level == "UNIVERSITARIO") $education = "soy universitari$genderFinalVowel";
		if ($profile->highest_school_level == "POSTGRADUADO") $education = "tengo estudios de postgrado";
		if ($profile->highest_school_level == "DOCTORADO") $education = "tengo un doctorado";
		
		// get occupation
		$occupation = (empty($profile->occupation) || strlen($profile->occupation) < 5) ? false : strtolower($profile->occupation);
		
		// get religion
		$religions = array(
				'ATEISMO' => 'soy ateo',
				'SECULARISMO' => 'no tengo creencia religiosa',
				'AGNOSTICISMO' => 'soy agn&oacute;stico',
				'ISLAM' => 'soy musulm&aacute;n',
				'JUDAISTA' => 'soy jud&iacute;o',
				'ABAKUA' => 'soy abaku&aacute;',
				'SANTERO' => 'soy santero',
				'YORUBA' => 'profeso la religi&oacute;n yoruba',
				'BUDISMO' => 'soy budista',
				'CATOLICISMO' => 'soy cat&oacute;lico',
				'OTRA' => 'tengo creencias religiosas',
				'CRISTIANISMO' => 'soy cristiano'
		);
		
		$religion = '';
		
		if ( ! empty($profile->religion)) $religion = $religions[$profile->religion];
		
		// create the message
		$message = "Hola y bienvenido a mi perfil. Yo soy $fullName";
		
		// create the message
		$message = "";
		if ( ! empty($religion)) $message .= " y $religion";
		if ( ! empty($profile->first_name)) $message .= "me llamo " . ucfirst(trim($profile->first_name)) . ", ";
		if ( ! empty($province)) $message .= "soy de $province, ";
		if ( ! empty($age)) $message .= "tengo $age a&ntilde;os, ";
		if ( ! empty($skin)) $message .= "soy $skin, ";
		if ( ! empty($eyes)) $message .= "de ojos $eyes, ";
		if ( ! empty($eyes)) $message .= "soy de pelo $hair, ";
		if ( ! empty($bodyType)) $message .= "$bodyType, ";
		if ( ! empty($education)) $message .= "$education, ";
		$message = trim($message, ", ");
		if ($occupation) $message .= " y trabajo como $occupation";
		$message .= ".";
		
		return ucfirst($message);
	}

	/**
	 * DB connection singleton
	 *
	 * @return Connection
	 */
	private function db ()
	{
		if (is_null($this->db)) $this->db = new Connection();
		return $this->db;
	}
}
