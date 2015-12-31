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

        $utils = new Utils();

        // temporal view
        $this->db()->deepQuery("DROP VIEW IF EXISTS __cupido_members; CREATE VIEW __cupido_members AS SELECT email, TRUE as active FROM person WHERE cupido = 1;");

        $user = $utils->getPerson($request->email);

        if ($user == false) {
            $response = new Response();
            $response->setResponseSubject("Cree su perfil en Apretaste!");
            $response->createFromTemplate('not_profile.tpl', array('email' => $request->email, "editProfileText" => $this->createProfileEditableText()));
            return $response;
        }

        $this->db()->deepQuery("UPDATE person SET cupido = 1 WHERE email = '{$request->email}';");

        $sql = '';
        if (is_array($user->interests)) {
            $sql = "SELECT email, sum(v) as vv FROM(\n";
            $j = 0;
            //foreach ($user->interests as $ints) {
            $ints = strtolower(implode(",", $user->interests));
            for ($i = 1; $i <= 10; $i++) {
                $j++;
                if ($j > 1)
                    $sql .= ' UNION ALL ';
                $sql .= " select email, IF('{$ints}' LIKE CONCAT('%',lower(split_str(interests,',',{$i})),'%'),1,0) as v from person WHERE CONCAT('%',lower(split_str(interests,',',{$i})),'%') <> '%%' \n";
            }
            //}
            $sql .= ") as subq GROUP BY email\n";
        }

        $subsql = $sql;
        $total_interests = count($user->interests);

        $sql = "SELECT email,\n";
        $sql .= "(select proximity FROM _province_proximity WHERE (province1 = person.province AND province2 = '{$user->province}') OR (province2 = person.province AND province1 = '{$user->province}')) / 100 * 15 as percent_proximity,\n";
        $sql .= "(select count(*) FROM _cupido_likes WHERE _cupido_likes.email2 = person.email) / (select count(*) FROM __cupido_members WHERE active = true)  * 15 as percent_likes,\n";
        $sql .= "(select person.skin = '{$user->skin}') * 5 as same_skin,\n" . "(select person.picture is not null) * 20 as having_picture,\n";
        $sql .= "(1 - (abs(datediff(person.date_of_birth, '{$user->date_of_birth}'))/365 / 20)) * 15 as age_proximity,\n";
        $sql .= "(select person.body_type = '{$user->body_type}')* 5 as same_body_type,\n";

        if ($total_interests > 0)
            $sql .= "(select vv FROM ($subsql) as subq1 WHERE email = person.email) / $total_interests * 25 as percent_preferences\n";
        else
            $sql .= "0 as percent_preferences\n";

        $sql .= " FROM person\n" . "WHERE person.email <> '{$request->email}' \n";

        if ($user->sexual_orientation == 'HETERO')
            $sql .= " AND person.gender <> '{$user->gender}' AND person.sexual_orientation <> 'HOMO'";

        if ($user->sexual_orientation == 'HOMO')
            $sql .= " AND person.gender = '{$user->gender}' AND person.sexual_orientation <> 'HETERO'";

        if ($user->sexual_orientation == 'BI')
            $sql .= " AND (person.sexual_orientation = 'BI' 
                            OR (person.sexual_orientation = 'HOMO' AND person.gender = '{$user->gender}')
                            OR (person.sexual_orientation = 'HETERO' AND person.gender <> '{$user->gender}')
                           )";

        $sql .= " AND person.marital_status = 'SOLTERO' \n";
        $sql .= "AND exists(select * from __cupido_members where __cupido_members.email = person.email AND __cupido_members.active = true)\n";
        $sql .= "AND abs(datediff(person.date_of_birth, '{$user->date_of_birth}')) / 365 <= 20\n";

        $subsql = $sql;

        $sql = " SELECT email, percent_proximity + percent_likes + same_skin + having_picture + age_proximity + same_body_type + percent_preferences as percent_match\n";
        $sql .= "FROM ($subsql) as subq2\n";
        $sql .= "ORDER BY percent_match DESC\n";
        $sql .= "LIMIT 5;\n";

        $list = $this->db()->deepQuery(trim($sql));

        $matchs = array();
        $images = array();
        $random = false;

        if (empty($list) || is_null($list)) {

            $sql = "SELECT * FROM __cupido_members 
                    WHERE __cupido_members.email <> '{$request->email}'
                      AND active = true 
                      AND NOT EXISTS (
                                SELECT * FROM _cupido_ignores 
                                WHERE _cupido_ignores.email2 <> __cupido_members.email
                      )
                      AND EXISTS (
                            SELECT * FROM person 
                            WHERE person.email = __cupido_members.email
                      )
                    ORDER BY rand() LIMIT 5";

            $list = $this->db()->deepQuery($sql);
            $random = true;
        }

        if (is_array($list))
            foreach ($list as $item) {
                $profile = $utils->getPerson($item->email);

                if ($profile === false)
                    continue;

                $images[] = empty($profile->picture) ? array() : array($profile->picture);

                if ($profile->full_name == '')
                    $profile->full_name = $profile->email;

                $matchs[] = $profile;
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
            $matchs[$k]->description = $desc;
        }

        $response = new Response();
        $response->setResponseSubject('Cupido: Personas de tu interes');

        if ($random == true)
            $response->setResponseSubject('No encontramos perfiles para ti, te mostamos algunos aleatorios');

        $response->createFromTemplate('basic.tpl', array('matchs' => $matchs, 'random' => $random), $images);

        // drop temporals
        $this->db()->deepQuery('DROP VIEW IF EXISTS __cupido_members;');

        return $response;
    }

    /**
     * Return true if $email is member of the network cupido
     * 
     * @param string $email
     */
    private function isMember($email) {

        $sql = "SELECT cupido FROM person WHERE email = '$email'";

        $find = $this->db()->deepQuery($sql);

        if (isset($find[0]))
            if (intval($find[0]->cupido) === 1)
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
        $response->setResponseSubject('Usted ha salido de la red de Cupido en Apretaste');
        $response->createFromText('Usted ha salido de la red de Cupido en Apretaste');
        return $response;
    }

    /**
     * Subservice LIKE
     */
    public function _like(Request $request) {

        if (!$this->isMember($request->email))
            return $this->getNotMemberResponse();

        $email = trim($request->query);

        if (!$this->isMember($email))
            return $this->getNotMemberResponse($email);

        $utils = new Utils();
        $user = $utils->getPerson($email);

        if ($this->isLike($request->email, $email)) {
            $response = new Response();
            $response->setResponseSubject('Ya a usted le gusta el perfil' . $email);
            $response->createFromText('Ya a usted le gusta el perfil ' . $email);
            return $response;
        }

        $sql = "INSERT INTO _cupido_likes (email1, email2) VALUES ('{$request->email}','$email');";
        $this->db()->deepQuery($sql);

        $response1 = new Response();
        $response1->setResponseSubject('A usted le gusta ' . $email);
        $response1->createFromTemplate('like.tpl', array('full_name' => $user->full_name));

        $user = $utils->getPerson($request->email);

        $response2 = new Response();
        $response2->setResponseEmail($email);
        $response2->setResponseSubject('Tienes un nuevo admirador(a)');
        $response2->createFromText("Has recibido este correo porque {$user->full_name} ({$user->email}) ha indicado que le gustas.");

        return array($response1, $response2);
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

        $email = trim($request->query);

        if (!$this->isMember($email))
            return $this->getNotMemberResponse($email);

        if ($this->isIgnore($request->email, $email)) {
            $response = new Response();
            $response->setResponseSubject('Ya usted habia ignorado a' . $email);
            $response->createFromText('Ya usted habia ignorado a' . $email);
            return $response;
        }

        $sql = "INSERT INTO _cupido_ignores (email1, email2) VALUES ('{$request->email}','$email');";
        $this->db()->deepQuery($sql);

        $response = new Response();
        $response->setResponseSubject('Usted ha ignorado a ' . $email);
        $response->createFromText('Ahora sabemos que usted ignora el perfil ' . $email);

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

    /**
     * To create the text that will be shown when the 
     * user click on the Edit Profile button  
     * */
    private function createProfileEditableText() {

        return urlencode(preg_replace('/\t/', '', "# Su nombre, por ejemplo: NOMBRE = Juan Perez Gutierres
			NOMBRE = 
			
			# Su Fecha de nacimiento, por ejemplo: CUMPLEANO = 23/08/1995
			CUMPLEANOS = 
			
			# Su Profesion resumida en una sola palabra, por ejemplo: Arquitecto
			PROFESION = 
			
			# Provincia donde vives
			PROVINCIA = 
			
			# Ciudad donde vives
			CIUDAD = 
			
			# Escoja entre: M o F, por ejemplo: SEXO = M
			SEXO = 
			
            # Escoja entre: HETERO, HOMO, BI. Este dato no se mostrara en su perfil
            PREFERENCIA SEXUAL =
            
			# Escoja entre: primario, secundario, tecnico, universitario, postgraduado, doctorado u otro
			NIVEL ESCOLAR = 
			
			# Escoja entre: soltero,saliendo,comprometido o casado
			ESTADO CIVIL = 
			
			# Escoja entre: trigueno, castano, rubio, negro, rojo, blanco u otro
			PELO =
			
			# Escoja entre: negro, blanco, mestizo u otro
			PIEL = 
			
			# Escoja entre: negro, carmelita, verde, azul, avellana u otro
			OJOS = 
			
			# Escoja entre delgado, medio, extra o atletico
			CUERPO = 
			
			# Liste sus intereses separados por coma, ejemplo: INTERESES = carros, playa, musica
			INTERESES =     
			
			# Y no olvide adjuntar su foto!"));
    }

}