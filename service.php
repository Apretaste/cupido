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

		// get my age
		$age = empty($user->date_of_birth) ? 0 : date_diff(date_create($user->date_of_birth), date_create('today'))->y;

		// Verifying profile completion
		$low_profile = false;
		if ($completion * 1 < 70 || empty($user->gender) || empty($user->full_name))
		{
			$low_profile = true;
		}

		// re-activate person in cupido
		if( ! $user->cupido)
		{
			$this->db()->deepQuery("UPDATE person SET cupido = 1 WHERE email = '{$request->email}';");
		}

		// create the where clause for the query
		$where  = " email <> '{$request->email}'";
		$where .= " AND email NOT IN (SELECT user2 FROM relations WHERE user1 = '{$request->email}' and relations.type = 'ignore')";
		if ($user->sexual_orientation == 'HETERO') $where .= " AND gender <> '{$user->gender}' AND sexual_orientation <> 'HOMO'";
		if ($user->sexual_orientation == 'HOMO') $where .= " AND gender = '{$user->gender}' AND sexual_orientation <> 'HETERO'";
		if ($user->sexual_orientation == 'BI') $where .= " AND (sexual_orientation = 'BI' OR (sexual_orientation = 'HOMO' AND gender = '{$user->gender}') OR (sexual_orientation = 'HETERO' AND gender <> '{$user->gender}'))";
		$where .= " AND (marital_status <> 'CASADO' OR marital_status IS NULL) ";
		$where .= " AND cupido = '1'";

		// create a comma separated list of all people sharing the same interests
		// if a person shares more than one interest, his/her email will show twice
		$interests = '';
		if (is_array($user->interests) && ! empty($user->interests))
		{
			$sql = "";
			$last = end($user->interests);
			foreach($user->interests as $interest)
			{
				$sql .= "SELECT email, LOWER('$interest') as interest FROM person WHERE LOWER(interests) LIKE LOWER('%$interest%') && email <> '{$request->email}'";
				if($interest != $last) $sql .= " UNION "; 
			}

			$res = $this->db()->deepQuery(trim($sql));
			foreach ($res as $r)
			{
				$interests .= $r->email.",";
			}
		}

		$list = null;
		
		// if profile is incomplete, then return the top ten profiles ...
		if ($low_profile)
		{
			// hot people === more likes
			$sql = "
			SELECT email, (select count(id) FROM relations WHERE relations.user2 = email) as number_likes,
			0 as percent_preferences
			FROM person 
			WHERE  email <> '{$request->email}'
				AND email NOT IN (SELECT user2 FROM relations WHERE user1 = '{$request->email}' and relations.type = 'ignore')
				AND gender = '{sex}'
				AND (marital_status <> 'CASADO' OR marital_status IS NULL)
				AND cupido = '1'
				AND picture = '1'
			ORDER BY number_likes desc 
			LIMIT {limit};";
			
			$list1 = $this->db()->deepQuery(str_replace(array('{sex}','{limit}'),array('F', 2), $sql));
			$list2 = $this->db()->deepQuery(str_replace(array('{sex}','{limit}'),array('M', 1), $sql));
			$list  = array_merge($list1, $list2);
		}
		else
		{
			// ... else return the best match
			// create subquery to calculate the percentages
			$subsql  = "SELECT email, ";
			$subsql .= "(select IFNULL(province, '') = '{$user->province}') * 50 as location_proximity, ";
			$subsql .= "(select IFNULL(marital_status, '') = 'SOLTERO') * 20 as percent_single, ";
			$subsql .= "(select count(id) FROM relations WHERE relations.user2 = email) * 5 as number_likes, ";
			$subsql .= "(select IFNULL(skin, '') = '{$user->skin}') * 5 as same_skin, "; 
			$subsql .= "(select picture = 1) * 30 as having_picture, ";
			$subsql .= "(ABS(IFNULL(YEAR(CURDATE()) - YEAR(date_of_birth), 0) - $age) < 20) * 15 as age_proximity,  ";
			$subsql .= "(select IFNULL(body_type, '') = '{$user->body_type}') * 5 as same_body_type, ";
			$subsql .= "(select IFNULL(religion, '') = '{$user->religion}') * 20 as same_religion, ";
			if ($interests != '') $subsql .= "(select (LENGTH('$interests')-LENGTH(REPLACE('$interests', email, '')))/LENGTH(email)) * 10  as percent_preferences ";
			else $subsql .= "0 as percent_preferences ";
			$subsql .= " FROM person WHERE $where  ";
	
			// create final query
			$sql  = "SELECT email, percent_preferences, number_likes, percent_single + location_proximity + number_likes + same_skin + having_picture + age_proximity + same_body_type + same_religion + percent_preferences as percent_match ";
			$sql .= "FROM ($subsql) as subq2 ";
			$sql .= "ORDER BY percent_match DESC, email ASC ";
			$sql .= "LIMIT 3; ";
	
			// Executing the query
			$list = $this->db()->deepQuery(trim($sql));
		} 
		
		$matchs = array();
		$images = array();
		$random = false;

		// If not matchs, return random profiles
		if (empty($list) || is_null($list))
		{
			$sql = "SELECT email FROM person WHERE $where ORDER BY rand() LIMIT 3";
			$list = $this->db()->deepQuery($sql);
			$random = true;
		}

		// Proccesing result
		if (is_array($list))
		{
			foreach ($list as $item)
			{
				$profile = $this->utils->getPerson($item->email);
				if($profile === false) continue;
				if( ! empty($profile->thumbnail)) $images[] = $profile->thumbnail;
				if(empty($profile->full_name)) $profile->full_name = "@".$profile->username;

				$profile->button_like = ! $this->isLike($request->email, $profile->email);
				$profile->description = $this->getProfileDescription($profile);
				$profile->popular = $item->number_likes > 5;
				$profile->commonInterests = $item->percent_preferences > 10;

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

		$responseContent = array(
			"matchs" => $matchs, 
			"profile" => $user,
			"noProfilePic" => empty($user->thumbnail),
			"noProvince" => empty($user->province),
			"fewInterests" => count($user->interests) <= 10,
			"completion" => $completion,
			"random" => $random
		);

		// Building response
		$response = new Response();
		if ($random) $response->setResponseSubject('No encontramos perfiles para ti, te mostramos algunos aleatorios');
		else $response->setResponseSubject('Personas de tu interes');
		$response->createFromTemplate('matches.tpl', $responseContent, $images);
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
		$response->createFromText('Haz salido de la red de Cupido. No recibir&aacute;s m&aacute;s emails de otros usuarios diciendo que le gustas ni aparecer&aacute;s en la lista de Cupido. Si deseas volver, simplemente usa el servicio nuevamente. &iexcl;Gracias por usar Apretaste!');
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
		$user = $this->utils->getPerson($request->email);
		$completion = $this->utils->getProfileCompletion($request->email);
			
		// Verifying profile completion
		/*if ($completion * 1 < 70 || empty($user->gender) || empty($user->full_name))
		{
			 $response = new Response();
			 $response->setResponseSubject("Cree su perfil en Apretaste!");
			 $response->createFromTemplate('not_profile.tpl', array('email' => $request->email));
			 return $response;
		}*/
		
		// check if you are a member
		if ( ! $this->isMember($request->email))
		{
			return $this->getNotMemberResponse();
		}

		if (empty(trim($request->query)))
		{
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
		
		if ( ! isset($emails[0]))
		{
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

		if ($this->isLike($request->email, $email))
		{
			$like = array('full_name' => $person->full_name,'username' => $person->username,'ya' => true);
		}
		else
		{
			$sql = "INSERT INTO relations (user1,user2,type,confirmed) VALUES ('{$request->email}','{$email}','like',1);";
			$this->db()->deepQuery($sql);
			
			// Generate a notification
			$this->utils->addNotification($email, 'cupido like', 'Tienes ' . $admirador_caption. '. Nuestro usuario @' . $currentUser->username. ' ha dicho que le gustas.', 'PERFIL @'.$currentUser->username);
			
			if (empty($person->full_name)) $person->full_name = "@".$person->username;
			$like = array('full_name' => $person->full_name,'username' => $person->username,'ya' => false);
		}

		$user = $this->utils->getPerson($request->email);

		$response2 = new Response();
		$response2->setResponseEmail($email);
		$response2->setResponseSubject('Tienes ' . $admirador_caption);
		$response2->createFromTemplate("like_you.tpl", array('user' => $user));

		$response1 = new Response();
		$response1->setResponseSubject('Te gusta @' . $person->username);
		if (empty($user->full_name)) $user->full_name = "@".$user->username;
		$response1->createFromTemplate('like.tpl', array('like' => $like, 'admirador' => $admirador_caption));

		return array($response1,$response2);
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

		if ( ! isset($emails[0]))
		{
			$response = new Response();
			$response->setResponseSubject("Te falta especificar el perfil a ocultar");
			$response->createFromText("No escribiste en el asunto los nombres de usuarios que deseas ocultar. Por ejemplo: CUPIDO OCULTAR pepe1");
			return $response;
		}

		$ignores_str = '';
		foreach ($emails as $email)
		{
			$person = $this->utils->getPerson($email);
			
			if ($email == $request->email)
			{
				$ignores[] = array(
					'username' => false,
					'message_before' => 'No puedes ocultarate a ti mismo.',
					'message_after' => 'Verifica que la direcci&oacute;n de correo que escribiste sea la correcta.'
				);
				continue;
			}
			
			if ( ! $this->isMember($email))
			{
				$un = $email;

				if (is_object($person)) $un = $person->username;

				$ignores[] = array(
					'username' => $un,
					'message_before' => '',
					'message_after' => ' no es miembro de la red de cupido en Apretaste.'
				);
				continue;
			}

			if ($this->isIgnore($request->email, $email))
			{
				$ignores[] = array(
					'username' => $person->username,
					'message_before' => 'A ',
					'message_after' => ' ya lo hab&iacute;as ocultado.'
				);
				continue;
			}

			$sql = "INSERT IGNORE INTO relations (user1, user2, type, confirmed) VALUES ('{$request->email}','$email','ignore', 1);";
			$this->db()->deepQuery($sql);
			
			$ignores[] = array(
				'username' => $person->username,
				'message_before' => 'Ocultaste satisfactoriamente a ',
				'message_after' => ''
			);
			
			$ignores_str .= '@'.$person->username;
		}

		$this->utils->addNotification($request->email, 'cupido', "Haz ocultado varios perfiles: {$ignores_str}. Los perfiles ocultados no se le mostrar&aacute;n m&aacute;s en las b&uacute;squedas de Cupido.");
		
		return new Response();
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
		
		foreach ($query as $q)
		{
			$part = trim($q);
			if ($part !== '') $parts[] = trim($part, " @,;");
		}
		
		$emails = array();
		foreach ($parts as $part)
		{
			$find = $this->db()->deepQuery("SELECT email FROM person WHERE username = '{$part}';");
			if (isset($find[0])) $emails[] = $find[0]->email;
			else $emails[] = $part;
		}

		return $emails;
	}

	/**
	 * Search if email1 like email2
	 */
	private function isLike ($email1, $email2)
	{
		$sql = "SELECT * FROM relations WHERE user1 = '$email1' AND user2 = '$email2';";
		$find = $this->db()->deepQuery($sql);
		return count($find) > 0;
	}

	/**
	 * Search if email1 ignore email2
	 */
	private function isIgnore ($email1, $email2)
	{
		$sql = "SELECT * FROM relations WHERE user1 = '$email1' AND user2 = '$email2' AND type = 'ignore';";
		$find = $this->db()->deepQuery($sql);
		if (isset($find[0])) if ($find[0]->user1 == $email1 && $find[0]->user2 == $email2) return true;
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

		if (is_null($email))
		{
			$response->setResponseSubject("Usted no forma parte la red Cupido");
			$response->createFromText("Usted no forma parte la red Cupido");
		}
		else
		{
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
		$fullName = empty($profile->full_name) ? $profile->username : trim($profile->full_name, " .,;");
		
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
		if ($profile->eyes == "NEGRO") $eyes = "negros";
		if ($profile->eyes == "CARMELITA") $eyes = "carmelita";
		if ($profile->eyes == "AZUL") $eyes = "azules";
		if ($profile->eyes == "VERDE") $eyes = "verdes";
		if ($profile->eyes == "AVELLANA") $eyes = "avellana";

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
		if ($profile->province == "ISLA_DE_LA_JUVENTUD") $province = "Isla de la Juventud";
		
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
		if(stripos($occupation, "studiant") !== false) $occupation = "";

		// get religion
		$religions = array(
			'ATEISMO' => "soy ate$genderFinalVowel",
			'SECULARISMO' => 'no tengo creencia religiosa',
			'AGNOSTICISMO' => "soy agn&oacute;stic$genderFinalVowel",
			'ISLAM' => 'soy musulm&aacute;n',
			'JUDAISTA' => "soy jud&iacute;o$genderFinalVowel",
			'ABAKUA' => 'soy abaku&aacute;',
			'SANTERO' => "soy santer$genderFinalVowel",
			'YORUBA' => 'profeso la religi&oacute;n yoruba',
			'BUDISMO' => 'soy budista',
			'CATOLICISMO' => "soy cat&oacute;lic$genderFinalVowel",
			'OTRA' => '',
			'CRISTIANISMO' => "soy cristian$genderFinalVowel"
		);
		$religion = empty($profile->religion) ? "" : $religions[$profile->religion];

		// create the message
		$message = "";
		if ( ! empty($profile->first_name)) $message .= "me llamo " . ucfirst(trim($profile->first_name)) . ", ";
		if ( ! empty($province)) $message .= "soy de $province, ";
		if ( ! empty($age)) $message .= "tengo $age a&ntilde;os, ";
		if ( ! empty($skin)) $message .= "soy $skin, ";
		if ( ! empty($eyes)) $message .= "de ojos $eyes, ";
		if ( ! empty($hair)) $message .= "soy de pelo $hair, ";
		if ( ! empty($bodyType)) $message .= "$bodyType, ";
		if ( ! empty($education)) $message .= "$education, ";
		if ( ! empty($religion)) $message .= "$religion,";
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
