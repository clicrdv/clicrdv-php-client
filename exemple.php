<?php

$apikey = 'xxxxxxxxxxxxxxxxx';
$username = 'moncompte@clicrdv.com';
$password = 'secret';

require('clicrdv-rest-client.php');
$c = new ClicRDVclient($apikey, $username, $password);

echo "Creation de la fiche John Doe...\n";
$r = $c->post('/api/v1/fiches', array(
  'fiche' => array(
    'firstname'=> 'John',
    'lastname'=> 'Doe'
  )
));
echo "Status code : ".$c->getStatusCode()."\n";
echo "Fiche crée ! id = ".$r->id."\n";

echo "Modification de la fiche...\n";
$r = $c->put('/api/v1/fiches/'.$r->id, array(
  'fiche' => array(
    'firstphone' => '01 83 62 04 04'
  )
));
echo "Status code : ".$c->getStatusCode()."\n";
echo "Fiche modifiée !\n";

echo "Récupération de la fiche...\n";
$r = $c->get('/api/v1/fiches/'.$r->id);
echo "Status code : ".$c->getStatusCode()."\n";
print_r($r);

echo "Suppression de la fiche John Doe...\n";
$r = $c->delete('/api/v1/fiches/'.$r->id);
echo "Status code : ".$c->getStatusCode()."\n";
echo "Fiche supprimée !\n"

?>