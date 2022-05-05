<?php

namespace Gene;

class Gene
{

    private $erreur_pages;
    private $erreur_tables;
    private $erreur_globale;

    public function generate_Nb()
    {
        session_start();
        if (isset($_SESSION['nb_pages']) and isset($_SESSION['nb_tables']) and isset($_SESSION['nom_projet']) and isset($_SESSION['chemin'])) {

            // Récupération des données 
            $nom = $_SESSION['nom_projet'];
            $chemin = $_SESSION['chemin'];
            $reg_generate = filter_input(INPUT_POST, "reg_generate", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if (isset($reg_generate)) {

                $erreur = 0;
                $erreur_globale = "";

                $nom_bd = filter_input(INPUT_POST, "nom_bd", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $user_bd = filter_input(INPUT_POST, "user_bd", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $password_bd = filter_input(INPUT_POST, "password_bd", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $hote_bd = filter_input(INPUT_POST, "hote_bd", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

                $projet = [$nom . "_Local", $nom . "_Prod"];



                // Pages
                for ($i = 0; $i < $_SESSION['nb_pages']; $i++) {
                    if (!isset($_POST['pages'][$i]) or $_POST['pages'][$i] == "") {
                        $erreur++;
                    }
                }

                // Table 
                for ($i = 0; $i < $_SESSION['nb_tables']; $i++) {
                    if (!isset($_POST['tables'][$i]) or $_POST['tables'][$i] == "") {
                        $erreur++;
                    }
                }





                // On créé le projet si il n'y a aucune erreur 
                if ($erreur == 0) {

                    // Vérifie si le répertoire existe :
                    if (is_dir($nom)) {

                        echo 'Le répertoire existe déjà!';
                    } else {



                        // Si le fichier n'existe pas on le créé 
                        if (!file_exists("$chemin/$nom")) {

                            // Création du nouveau répertoire
                            if (!mkdir("$chemin/$nom")) {

                                $erreur++;
                            } else {
                                $chemin_ini = $chemin;


                                $erreur_globale = "Le projet a été créé *";

                                for ($y = 0; $y < 2; $y++) {
                                    $chemin = "$chemin_ini/$nom/" . $projet[$y];
                                    mkdir($chemin);

                                    // On créé tout les sous-dossier et fihiers nécessaire 
                                    mkdir("$chemin/view");

                                    // Pages 
                                    for ($i = 0; $i < $_SESSION['nb_pages']; $i++) {

                                        $nom_pages = $_POST['pages'][$i];
                                        $Nom_view = ucfirst($nom_pages);
                                        $Nom_fichier = $Nom_view;
                                        $controller = $Nom_fichier . "_Controller";
                                        $view = fopen("$chemin/view/$nom_pages.php", 'w');
                                        if ($_SESSION['nb_tables'] > 0) {
                                            fwrite($view, "<?php

                                            // Database connexion 
                                            require('../Config/setup.php');
                                            
                                            // Controller 
                                            require('../Controller/$controller.php');
                                            
                                            
                                            // utilisation de contact class 
                                            use $Nom_view\\$Nom_view;
                                            
                                            // appel de la class
                                            $$nom_pages = new $Nom_view;
                                            
                                            // Lancement de la fonction
                                            //\$nom_pages->nom_methode();
                                            
                                            
                                            ?>");
                                        } else {
                                            fwrite($view, "<?php
                                            
                                            // Controller 
                                            require('../Controller/$controller.php');
                                            
                                            
                                            // utilisation de contact class 
                                            use $Nom_view\\$Nom_view;
                                            
                                            // appel de la class
                                            $$nom_pages = new $Nom_view;
                                            
                                            // Lancement de la fonction
                                            //\$nom_pages->nom_methode();
                                            
                                            
                                            ?>");
                                        }
                                    }

                                    mkdir("$chemin/view/partials");
                                    fopen("$chemin/view/partials/footer.php", 'w');
                                    fopen("$chemin/view/partials/header.php", 'w');
                                    fopen("$chemin/view/partials/head.php", 'w');

                                    mkdir("$chemin/css");
                                    fopen("$chemin/css/style.css", 'w');

                                    mkdir("$chemin/images");

                                    // Dztabase 
                                    if ($_SESSION['nb_tables'] > 0) {

                                        // Config database 
                                        mkdir("$chemin/Config");

                                        $core = fopen("$chemin/Config/Core.php", 'c+b');
                                        fwrite($core, "<?php 

                                    class Core{
                                        static \$bdd;
                                    
                                        static function getDatabase(){
                                    
                                            if(!self::\$bdd){
                                                return new Database('$user_bd','$password_bd', '$nom_bd');
                                            }
                                            return self::\$bdd;
                                        }
                                    }");

                                        $database = fopen("$chemin/Config/Database.php", 'c+b');
                                        fwrite($database, "<?php

                                    class Database
                                    {
                                    
                                        private \$bdd;
                                        private \$erreur_requete;
                                    
                                        public function __construct(\$user, \$password, \$db_name, \$host = '$hote_bd')
                                        {
                                            try {
                                                // Connexion bd 
                                                \$this->bdd = new PDO('mysql:host='.\$host.';dbname='.\$db_name.'', \$user, \$password);
                                                // set the PDO error mode to exception
                                                \$this->bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                            } catch (PDOException \$e) {
                                                echo 'Connection failed: ' . \$e->getMessage();
                                            }
                                        }
                                    }");


                                        $setup = fopen("$chemin/Config/setup.php", 'c+b');
                                        fwrite($setup, "<?php \n require('./Core.php'); \n require('./Database.php');");

                                        // Model 
                                        mkdir("$chemin/Model");

                                        for ($i = 0; $i < $_SESSION['nb_tables']; $i++) {

                                            $nom_tables = $_POST['tables'][$i];
                                            $Name_tables = ucfirst($nom_tables);
                                            $Name_page = $Name_tables . "_Model";
                                            $model = fopen("$chemin/Model/$Name_page.php", 'c+b');
                                            fwrite($model, "<?php

                                        // Permet d'avoir le fichier nommé contact un seul fois le rendre unique
                                        namespace Model$Name_tables;
                                        
                                        use Core;
                                        class Model$Name_tables {
                                            // Select
                                            public function select_$nom_tables()
                                            {
                                                // Connexion a la bd
                                                \$bdd = Core::getDatabase();
                                                // Insertion des donné dans la table 
                                                \$sql = \$bdd->query('Votre requête');
                                                return \$sql;
                                            }
                                        }");
                                        }
                                    }


                                    mkdir("$chemin/js");

                                    mkdir("$chemin/Controller");

                                    for ($i = 0; $i < $_SESSION['nb_pages']; $i++) {

                                        $nom_pages = $_POST['pages'][$i];
                                        $Name_pages = ucfirst($nom_pages);
                                        $Nom_fichier = $Name_pages . "_Controller";
                                        $controller = fopen("$chemin/Controller/$Nom_fichier.php", 'c+b');
                                        fwrite($controller, "<?php


                                    // Permet d'avoir le fichier nommé contact un seul fois le rendre unique 
                                    namespace $Name_pages;
                                    
                                    // use Model_delapage\Model_Nom;
                                    
                                    // require('../Model/Model_Nom.php');
                                    
                                    
                                    // class 
                                    class $Name_pages
                                    {

                                    }");
                                    }
                                }



                                $this->erreur_globale = $erreur_globale;
                            }
                        } else {
                            $erreur_globale = "Le dossier existe déjà *";
                            $this->erreur_globale = $erreur_globale;
                        }
                    }
                } else {
                    $erreur_globale = "Champs invalide *";
                    $this->erreur_globale = $erreur_globale;
                }
            }
        } else {
            header('location:../');
        }
    }

    public function getErreurPages()
    {
        return $this->erreur_pages;
    }

    public function getErreurTables()
    {
        return $this->erreur_tables;
    }

    public function getErreur()
    {
        return $this->erreur_globale;
    }
}
