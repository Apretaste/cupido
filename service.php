<?php

class Cupido extends Service {

    private $db = null;

    /**
     * Function executed when the service is called
     *
     * @param Request
     * @return Response
     **/
    public function _main(Request $request) {

        $user = $this->utils->getPerson($request->email);
        $completion = $this->utils->getProfileCompletion($request->email);

        // Verifying profile completion
        if ($completion * 1 < 70 || empty($user->gender) || empty($user->full_name)) {
            $response = new Response();
            $response->setResponseSubject("Cree su perfil en Apretaste!");
            $response->createFromTemplate('not_profile.tpl', array('email' => $request->email, "editProfileText" => $this->utils->createProfileEditableText($request->email)));
            return $response;
        }

        // Activating person in cupido
        $this->db()->deepQuery("UPDATE person SET cupido = 1 WHERE email = '{$request->email}';");

        // Match algorithm - building sql query

        $common_filter = " person.email <> '{$request->email}'
                      AND cupido = 1 
                      AND NOT EXISTS (
                                SELECT * FROM _cupido_ignores 
                                WHERE _cupido_ignores.email2 = person.email
                      ) ";

        if ($user->sexual_orientation == 'HETERO')
            $common_filter .= " AND person.gender <> '{$user->gender}' AND person.sexual_orientation <> 'HOMO'";

        if ($user->sexual_orientation == 'HOMO')
            $common_filter .= " AND person.gender = '{$user->gender}' AND person.sexual_orientation <> 'HETERO'";

        if ($user->sexual_orientation == 'BI')
            $common_filter .= " AND (person.sexual_orientation = 'BI' 
                            OR (person.sexual_orientation = 'HOMO' AND person.gender = '{$user->gender}')
                            OR (person.sexual_orientation = 'HETERO' AND person.gender <> '{$user->gender}')
                           )";


        $common_filter .= " AND person.marital_status = 'SOLTERO' \n";
        $common_filter .= " AND person.cupido = 1\n";
        $common_filter .= " AND abs(datediff(person.date_of_birth, '{$user->date_of_birth}')) / 365 <= 20";

        $sql = '';

        if (is_array($user->interests)) {

            // Interests

            $sql = "SELECT email, sum(v) as vv FROM(\n";
            $j = 0;
            $ints = strtolower(implode(",", $user->interests));
            for ($i = 1; $i <= 10; $i++) {
                $j++;
                if ($j > 1)
                    $sql .= ' UNION ALL ';
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

        if ($total_interests > 0)
            $sql .= "(select vv FROM ($subsql) as subq1 WHERE email = person.email) / $total_interests * 25 as percent_preferences\n";
        else
            $sql .= "0 as percent_preferences\n";

        $sql .= " FROM person\n WHERE $common_filter \n";

        $subsql = $sql;

        $sql = " SELECT email, percent_proximity + percent_likes + same_skin + having_picture + age_proximity + same_body_type + percent_preferences as percent_match\n";
        $sql .= "FROM ($subsql) as subq2\n";
        $sql .= "ORDER BY percent_match DESC\n";
        $sql .= "LIMIT 3;\n";

        // Executing the query
        $list = $this->db()->deepQuery(trim($sql));

        $matchs = array();
        $images = array();
        $random = false;

        if (empty($list) || is_null($list)) { // If not matchs, return random profiles

            $sql = "SELECT email FROM person 
                    WHERE $common_filter ORDER BY rand() LIMIT 3";

            $list = $this->db()->deepQuery($sql);
            $random = true;
        }

        // Proccesing result
        if (is_array($list))
            foreach ($list as $item) {
                $profile = $this->utils->getPerson($item->email);

                if ($profile === false)
                    continue;

                $images[] = empty($profile->picture) ? array() : array($profile->picture);

                if ($profile->full_name == '')
                    $profile->full_name = $profile->email;

                $profile->button_like = true;

                if ($this->isLike($request->email, $profile->email))
                    $profile->button_like = false;
                    
                $matchs[] = $profile;
            }

        // Not found :(
        if (!isset($list[0])) {
            $response = new Response();
            $response->setResponseSubject('No encontramos perfiles para ti');
            $response->createFromText('No encontramos perfiles para ti');
            return $response;
        }


        // Building descriptions
        foreach ($matchs as $k => $m) {
            $desc = '';

            if (!empty($m->full_name))
                $desc .= $m->full_name;

            if (!empty($m->gender)) {
                if ($m->gender == 'M')
                    $desc .= ', hombre ';
                if ($m->gender == 'F')
                    $desc .= ', mujer ';
            }

            if (!empty($m->sexual_orientation)) {
                if ($m->sexual_orientation == 'HOMO')
                    $desc .= ' homosexual ';
                if ($m->sexual_orientation == 'HETERO')
                    $desc .= ' heretosexual, ';
                if ($m->sexual_orientation == 'BI')
                    $desc .= ' bisexual ';
            }

            $find = $this->db()->deepQuery("SELECT abs(datediff(CURRENT_DATE,date_of_birth)) / 365 as age FROM person WHERE email = '{$m->email}';");

            if (intval($find[0]->age) >= 18)
                $desc .= ' de ' . intval($find[0]->age) . ' a&ntilde;os';

            if (!empty($m->sking))
                $desc .= ', piel ' . $m->skin;

            if (!empty($m->province))
                $desc .= ', de ' . ucwords(strtolower(str_replace('_', ' ', $m->province)));

            if (!empty($m->city))
                $desc .= ', ' . ucwords(strtolower(str_replace('_', ' ', $m->city)));

            if (!empty($m->body_type)) {
                $bt = trim(strtolower(str_replace('_', ' ', $m->body_type)));
                $bt = substr($bt, 0, strlen($bt) - 1);

                if ($m->gender == 'M')
                    $bt .= 'o';

                if ($m->gender == 'F')
                    $bt .= 'a';

                $desc .= ', ' . $bt;
            }

            if (!empty($m->hair)) {
                if ($m->hair != 'OTRO')
                    $desc .= ', pelo ' . str_replace('n', '&ntilde;', strtolower($m->hair));
            }

            if (!empty($m->eyes)) {
                $e = strtolower($m->eyes);
                if (stripos('aeiou', $e[strlen($e) - 1]) !== false)
                    $e .= 's';
                else
                    $e .= 'es';
                $desc .= ', ojos ' . $e;
            }

            $matchs[$k]->age = intval($find[0]->age);

            $desc = str_replace('  ', ' ', $desc);
            $matchs[$k]->description = $desc;
        }

        // Building response
        $response = new Response();
        $response->setResponseSubject('Cupido: Personas de tu interes');

        if ($random == true)
            $response->setResponseSubject('No encontramos perfiles para ti, te mostamos algunos aleatorios');

        $response->createFromTemplate('basic.tpl', array('matchs' => $matchs, 'random' => $random), $images);

        return $response;
    }

    /**
     * Return true if $email is member of the network cupido
     * 
     * @param string $email
     */
    private function isMember($email) {

        $sql = "SELECT cupido FROM person WHERE email = '$email' AND cupido = 1";

        $find = $this->db()->deepQuery($sql);

        if (isset($find[0]))
            return true;

        return false;
    }

    /**
     * Subservice SALIR
     */
    public function _salir(Request $request) {

        if (!$this->isMember($request->email))
            return $this->getNotMemberResponse();

        $this->db()->deepQuery("UPDATE person SET cupido = 1 WHERE email = '{$request->email}';");

        $response = new Response();
        $response->setResponseSubject('Haz salido de la red de Cupido en Apretaste');
        $response->createFromText('Haz salido de la red de Cupido en Apretaste. Agradeceremos que nos escr&iacute;bas al soporte para saber tu motivo.');
        return $response;
    }

    /**
     * Subservice LIKE
     * 
     * @param Request $request
     * @return Reponse/Array
     */
    public function _like(Request $request) {

        if (!$this->isMember($request->email))
            return $this->getNotMemberResponse();

        $current_user = $this->utils->getPerson($request->email);

        $admirador_caption = 'un(a) admirador(a)';

        if ($current_user->gender = 'F')
            $admirador_caption = 'una admiradora';

        if ($current_user->gender = 'M')
            $admirador_caption = 'un admirador';

        $emails = $this->getEmailsFromRequest($request);

        $responses = array();

        $likes = array();

        foreach ($emails as $email) {

            if (!$this->isMember($email))
                return $this->getNotMemberResponse($email);

            $user = $this->utils->getPerson($email);

            if ($this->isLike($request->email, $email)) {
                $likes[] = array(
                    'full_name' => $user->full_name,
                    'username' => $user->username,
                    'ya' => true);
                continue;
            }

            $sql = "INSERT INTO _cupido_likes (email1, email2) VALUES ('{$request->email}','$email');";
            $this->db()->deepQuery($sql);

            if (empty($user->full_name))
                $user->full_name = $user->username;

            $likes[] = array(
                'full_name' => $user->full_name,
                'username' => $user->username,
                'ya' => false);

            $user = $this->utils->getPerson($request->email);

            $response2 = new Response();
            $response2->setResponseEmail($email);
            $response2->setResponseSubject('Tienes ' . $admirador_caption);
            $response2->createFromText("Has recibido este correo porque {$user->full_name} ({$user->email}) ha indicado que le gustas.");

            $responses[] = $response2;
        }

        $response1 = new Response();
        $response1->setResponseSubject('Haz indicado que te gustan los siguientes perfiles');

        if (empty($user->full_name))
            $user->full_name = $email;

        $response1->createFromTemplate('like.tpl', array('likes' => $likes, 'admirador' => $admirador_caption));

        $responses[] = $response1;

        return $responses;
    }

    /**
     * Return a list of emails from request query
     * 
     * @param Request $request
     * @return array
     */
    private function getEmailsFromRequest($request) {

        $query = explode(" ", $request->query);

        $parts = array();

        foreach ($query as $q) {
            $part = trim($q);
            if ($part !== '')
                $parts[] = $part;
        }

        $emails = array();
        foreach ($parts as $part) {
            $find = $this->db()->deepQuery("SELECT email FROM person WHERE username = '{$part}';");

            if (isset($find[0])) {
                $emails[] = $find[0]->email;
            } else
                $emails[] = $part;
        }

        return $emails;
    }

    /**
     * Search if email1 like email2
     */
    private function isLike($email1, $email2) {

        $sql = "SELECT * FROM _cupido_likes WHERE email1 = '$email1' AND email2 = '$email2';";
        $find = $this->db()->deepQuery($sql);

        if (isset($find[0]))
            if ($find[0]->email1 == $email1 && $find[0]->email2 == $email2)
                return true;

        return false;
    }

    /**
     * Subservice IGNORAR
     */
    public function _ignorar(Request $request) {

        if (!$this->isMember($request->email))
            return $this->getNotMemberResponse();

        $emails = $this->getEmailsFromRequest($request);
        $ignores = array();

        if (!isset($emails[0])) {
            $response = new Response();
            $response->setResponseSubject("Te falta especificar el perfil a ignorar");
            $response->createFromText("No escribiste en el asunto los nombres de usuarios que deseas ignorar. Por ejemplo: CUPIDO IGNORAR pepe1");
            return $response;
        }

        foreach ($emails as $email) {

            $person = $this->utils->getPerson($email);

            if ($email == $request->email) {
                $ignores[] = array(
                    'username' => false,
                    'message_before' => 'No puedes ignorarate a ti mismo.',
                    'message_after' => 'Verifica que la direcci&oacute;n de correo que escribiste sea la correcta.');
                continue;
            }

            if (!$this->isMember($email)) {
                $un = $email;

                if (is_object($person))
                    $un = $person->username;

                $ignores[] = array(
                    'username' => $un,
                    'message_before' => '',
                    'message_after' => ' no es miembro de la red de cupido en Apretaste.');
                continue;
            }

            if ($this->isIgnore($request->email, $email)) {
                $ignores[] = array(
                    'username' => $person->username,
                    'message_before' => 'A ',
                    'message_after' => ' ya lo habias ignorado.');
                continue;
            }

            $sql = "INSERT INTO _cupido_ignores (email1, email2) VALUES ('{$request->email}','$email');";
            $this->db()->deepQuery($sql);

            $ignores[] = array(
                'username' => $person->username,
                'message_before' => 'Ahora sabemos que ingoras a ',
                'message_after' => '');

        }

        $response = new Response();
        $response->setResponseSubject('Haz ignorado los siguientes perfiles');
        $response->createFromTemplate('ignore.tpl', array('ignores' => $ignores));

        return $response;
    }

    /**
     * Search if email1 ignore email2
     */
    private function isIgnore($email1, $email2) {
        $sql = "SELECT * FROM _cupido_ignores WHERE email1 = '$email1' AND email2 = '$email2';";
        $find = $this->db()->deepQuery($sql);

        if (isset($find[0]))
            if ($find[0]->email1 == $email1 && $find[0]->email2 == $email2)
                return true;

        return false;
    }

    /**
     * Return common not member response 
     */
    private function getNotMemberResponse($email = null) {
        $response = new Response();

        if (is_null($email)) {
            $response->setResponseSubject('Usted no forma parte la red de Cupido en Apretaste');
            $response->createFromText('Usted no forma parte la red de Cupido en Apretaste');
        } else {
            $response = new Response();
            $response->setResponseSubject('El usuario no forma parte la red de Cupido en Apretaste');
            $response->createFromText('El usuario ' . $email . ' no forma parte la red de Cupido en Apretaste');
        }

        return $response;
    }

    /**
     * DB connection singleton
     */
    private function db() {
        if (is_null($this->db))
            $this->db = new Connection();
        return $this->db;
    }

}
