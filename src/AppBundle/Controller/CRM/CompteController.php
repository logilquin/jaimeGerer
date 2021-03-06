<?php

namespace AppBundle\Controller\CRM;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use AppBundle\Entity\CRM\Compte;
use AppBundle\Entity\CRM\Contact;
use AppBundle\Entity\Settings;
use AppBundle\Entity\Rapport;

use AppBundle\Form\CRM\CompteType;
use AppBundle\Form\CRM\CompteFilterType;
use AppBundle\Form\CRM\CompteFusionnerType;
use AppBundle\Form\CRM\CompteFusionnerEtape2Type;
use AppBundle\Form\CRM\ContactType;
use AppBundle\Form\SettingsType;
use AppBundle\Form\CRM\CompteImportType;
use AppBundle\Form\CRM\CompteImportMappingType;

use AppBundle\Entity\Compta\CompteComptable;

use libphonenumber\PhoneNumberFormat;

use FOS\RestBundle\Controller\Annotations as Rest;

class CompteController extends Controller
{
	/**
	 * @Route("/crm/compte/liste", name="crm_compte_liste")
	 */
	public function compteListeAction()
	{
		return $this->render('crm/compte/crm_compte_liste.html.twig');
	}

	/**
	 * @Route("/crm/compte/liste/ajax", name="crm_compte_liste_ajax")
	 */
	public function compteListeAjaxAction()
	{
		$requestData = $this->getRequest();
		$arr_sort = $requestData->get('order');
		$arr_cols = $requestData->get('columns');

		$col = $arr_sort[0]['column'];

		$repository = $this->getDoctrine()->getManager()->getRepository('AppBundle:CRM\Compte');
		$repositoryContact = $this->getDoctrine()->getManager()->getRepository('AppBundle:CRM\Contact');

		$arr_search = $requestData->get('search');

		$list = $repository->findForList(
				$this->getUser()->getCompany(),
				$requestData->get('length'),
				$requestData->get('start'),
				$arr_cols[$col]['data'],
				$arr_sort[0]['dir'],
				$arr_search['value']
		);
		foreach( $list as $k=>$v )
		{
			$fusion = $repositoryContact->findBy(array('compte' => $v['id']));
			$v['fusion'] = count($fusion) > 1 ? 1 : 0;
			$list[$k] = $v;
		}

		$response = new JsonResponse();
		$response->setData(array(
				'draw' => intval( $requestData->get('draw') ),
				'recordsTotal' => $repository->count($this->getUser()->getCompany()),
				'recordsFiltered' => $repository->countForList($this->getUser()->getCompany(), $arr_search['value']),
				'aaData' => $list,
		));

		return $response;
	}

	/**
	 * @Route("/crm/compte", name="crm_compte_datatables")
	 */
	public function compteDatatablesAction(Compte $compte)
	{
	}
	/**
	 * @Route("/crm/compte/voir/{id}", name="crm_compte_voir", options={"expose"=true})
	 */
	public function compteVoirAction(Compte $compte)
	{
		$contactRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:CRM\Contact');
		$opportuniteRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:CRM\Opportunite');
		$docPrixRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:CRM\DocumentPrix');

		$arr_contacts = $contactRepository->findByCompte($compte);
		$arr_opportunites = $opportuniteRepository->findByCompte($compte);
		$arr_devis = $docPrixRepository->findBy(array('compte' => $compte, 'type' => 'DEVIS'));
		$arr_factures = $docPrixRepository->findBy(array('compte' => $compte, 'type' => 'FACTURE'));


		return $this->render('crm/compte/crm_compte_voir.html.twig', array(
			'compte' => $compte,
			'arr_contacts' => $arr_contacts,
			'arr_opportunites' => $arr_opportunites,
			'arr_devis' => $arr_devis,
			'arr_factures' => $arr_factures,
		));
	}

	/**
	 * @Route("/crm/compte/ajouter", name="crm_compte_ajouter")
	 */
	public function compteAjouterAction()
	{
		$compte = new Compte();
		$compte->setUserGestion($this->getUser());
		$compte->setCompany($this->getUser()->getCompany());
		$form = $this->createForm(
					new CompteType(
							$compte->getUserGestion()->getId(),
							$this->getUser()->getCompany()->getId()
					),
					$compte
				);
		$form->add('addressPicker', 'text', array(
			'label' => 'Veuillez renseigner l\'adresse ici',
			'mapped' => false,
			'required' => false,
		));
		$request = $this->getRequest();
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {

			$compte->setDateCreation(new \DateTime(date('Y-m-d')));
			$compte->setUserCreation($this->getUser());
			$em = $this->getDoctrine()->getManager();

			$nom = strtoupper(str_replace(' ','',$compte->getNom()));
			$code = substr($nom,0,4);
			$i=2;

			$ccRepo = $em->getRepository('AppBundle:Compta\CompteComptable');
			$arrCodes = $ccRepo->findCodes($this->getUser()->getCompany());

			while(in_array($code, $arrCodes)){
				$code = substr($nom,0,4);
				$code = $code.$i;
				$i++;
			}

			$em->persist($compte);
			$em->flush();

			return $this->redirect($this->generateUrl(
					'crm_compte_voir',
					array('id' => $compte->getId())
			));
		}

		return $this->render('crm/compte/crm_compte_ajouter.html.twig', array(
			'form' => $form->createView()
		));
	}

	/**
	 * @Route("/crm/compte/fusionner/{id}", name="crm_compte_fusionner")
	 */
	public function compteFusionnerAction(Compte $compte)
	{
		$em = $this->getDoctrine()->getManager();
		$request = $this->getRequest();
		//~ var_dump($request->get('id')); exit;
		$new_contact = new Compte();
		$new_contact->setUserGestion($this->getUser());
		$form = $this->createForm(
				new CompteFusionnerType(
						$this->getUser()->getId(),
						$this->get('router'),
						$request->get('id')
				),
				$new_contact
		);

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid() && 1==0) {

			$new_contact->setDateCreation(new \DateTime(date('Y-m-d')));
			$new_contact->setUserCreation($this->getUser());
			$em = $this->getDoctrine()->getManager();
			$em->persist($new_contact);
			$em->flush();

			return $this->redirect($this->generateUrl(
					'crm_contact_voir',
					array('id' => $contact->getId())
			));
		}

		return $this->render('crm/compte/crm_compte_fusionner.html.twig', array(
				'form' 		=> $form->createView(),
				'compte' 	=> $compte,
				'step'		=> 'step1'
		));
	}

	/**
	 * @Route("/crm/compte/fusionner/etape2/{id}", name="crm_compte_fusionner_etape2")
	 */
	public function compteFusionnerEtape2Action(Compte $compte)
	{
		//~ echo "hich"; exit;
		$em = $this->getDoctrine()->getManager();
		$request = $this->getRequest();
		//~ var_dump($_POST);
		//~ $contact = new Contact();
		//~ $compte->setUserGestion($this->getUser());
		$form = $this->createForm(
				new compteFusionnerType(
						$this->getUser()->getId(),
						$this->get('router'),
						$request->get('id')
				),
				$compte
		);

		//~ $request = $this->getRequest();
		$form->handleRequest($request);
		//~ if ($form->isSubmitted() && $form->isValid()) {
			$posts = $request->request->get($form->getName());
			$repository = $em->getRepository('AppBundle:CRM\Compte');
			$first_compte = $repository->findOneById($request->get('id'));
			$second_compte = $repository->findOneById($posts['compte']);
			$form = $this->createForm(
					new CompteFusionnerEtape2Type(
							$first_compte,
							$second_compte,
							$this->get('router')
					),
					$compte
			);

			return $this->render('crm/compte/crm_compte_fusionner_etape2.html.twig', array(
					'form' => $form->createView(),
					'compte' => $compte,
					'step'	=> 'step2',
					'first_compte' => $first_compte,
					'second_compte' => $second_compte
			));
		//~ }

		return $this->render('crm/compte/crm_compte_fusionner.html.twig', array(
				'form' 		=> $form->createView(),
				'compte' 	=> $compte,
				'step'		=> 'step2'
		));
	}

	/**
	 * @Route("/crm/compte/fusionner/execution/{id}", name="crm_compte_fusionner_execution")
	 * @Method("POST")
	 */
	public function compteFusionnerExecutionAction(Compte $compte)
	{
		//~ var_dump($_POST); exit;
		$em = $this->getDoctrine()->getManager();

		$request = $this->getRequest();
		$posts = array_values($request->request->all());

		$repository = $em->getRepository('AppBundle:CRM\Compte');
		$first_compte = $repository->findOneById($request->get('id'));
		$second_compte = $repository->findOneById($posts[0]['second_compte_id']);
//~ var_dump($first_compte);
//~ var_dump($second_compte);
		//~ exit;


		$form = $this->createForm(
				new CompteFusionnerEtape2Type(
						$first_compte,
						$second_compte,
						$this->get('router')
				),
				$compte
		);

		$form->handleRequest($request);

		//~ if ($form->isSubmitted() && $form->isValid()) {
			$champs = $em->getClassMetadata('AppBundle:CRM\Compte')->getFieldNames();
			$compte->setDateEdition(new \DateTime(date('Y-m-d')));
			$compte->setUserEdition($this->getUser());

			// Temoin pour vérifier qu'au moins une donnée du compte2 est choisi => màj
			$fusionner_compte = false;

			foreach( $posts[0] as $k=>$v )
			{
				if( substr($v, -1) == 2 )
				{
					$fusionner_compte = true;

					// valeur choisie est celle du contact 2, on controle le champ pour le setteur de la classe Contact
					$champ = substr($v, 0, -1);
					if( $champ == 'adresse' )
					{
						$compte->setAdresse($second_compte->getAdresse());
						$compte->setCodePostal($second_compte->getCodePostal());
						$compte->setVille($second_compte->getVille());
						$compte->setRegion($second_compte->getRegion());
						$compte->setPays($second_compte->getPays());
					}
					else if( $champ == 'userGestion' )
					{
						$compte->setUserGestion($second_compte->getUserGestion());
					}
					else if( in_array($champ, $champs) )
					{
						// Le champ existe dans la bdd, on màj
						$methodSet = 'set'.ucfirst($champ);
						$methodGet = 'get'.ucfirst($champ);
						eval("\$var = \$second_compte->$methodGet();");
						//var_dump($var);
						eval('$compte->$methodSet($var);');
					}
				}
			}
						//var_dump($compte);
			//exit;

			if( $fusionner_compte )
			{
				$em->persist($compte);
				$em->flush();
			}
			//~ var_dump($second_compte->getId());

			// màj dans les tables : devis, factures, opportunités, contacts
			// contacts
			$repositoryContact = $em->getRepository('AppBundle:CRM\Contact');
			$Compte2Contact = $repositoryContact->findBy(
														array('compte' => $second_compte),
														array('id' => 'DESC')
													);
			foreach( $Compte2Contact as $Contact )
			{
				$Contact->setCompte($first_compte);
				$em->persist($Contact);
			}
			// devis etfactures
			$repositoryDevis = $em->getRepository('AppBundle:CRM\DocumentPrix');
			$Compte2Devis = $repositoryDevis->findBy(
													array('compte' => $second_compte, 'type' => array('DEVIS', 'FACTURE') ),
													array('id' => 'DESC')
												);
			foreach( $Compte2Devis as $Devis )
			{
				$Devis->setCompte($first_compte);
				$em->persist($Devis);
			}
			//~ var_dump($Compte2Devis); exit;

			// opportunités
			$repositoryOpportunite = $em->getRepository('AppBundle:CRM\Opportunite');
			$Compte2Opportunite = $repositoryOpportunite->findBy(
													array('compte' => $second_compte),
													array('id' => 'DESC')
												);
			foreach( $Compte2Opportunite as $Opportunite )
			{
				$Opportunite->setCompte($first_compte);
				$em->persist($Opportunite);
			}
			//~ var_dump($Contact2Opportunite); exit;

			//~ var_dump($Contact2Impulsion); exit;

			$em->flush();
			$em->remove($second_compte);
			$em->flush();
			echo 1; exit;

		//~ }

		return $this->render('crm/compte/crm_compte_fusionner_etape2.html.twig', array(
				'form' => $form->createView(),
				'compte' => $compte,
				'step'	=> 'step2',
				'first_compte' => $first_compte,
				'second_compte' => $second_compte
		));
	}

	/**
	 * @Route("/crm/compte/ajouter_modal", name="crm_compte_ajouter_modal")
	 */
	public function compteAjouterModalAction()
	{
		$compte = new Compte();
		$compte->setUserGestion($this->getUser());
		$compte->setCompany($this->getUser()->getCompany());
		$form = $this->createForm(
					new CompteType(
							$compte->getUserGestion()->getId(),
							$this->getUser()->getCompany()->getId(),
							$this->get('router')->generate('crm_compte_ajouter_modal')
					),
					$compte
				);
		$form->add('addressPicker1', 'text', array(
			'label' => 'Veuillez renseigner l\'adresse ici',
			'mapped' => false,
			'required' => false,
		));
		$request = $this->getRequest();
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {

			$compte->setDateCreation(new \DateTime(date('Y-m-d')));
			$compte->setUserCreation($this->getUser());
			$em = $this->getDoctrine()->getManager();
			$em->persist($compte);
			$em->flush();

			return new JsonResponse(array(
				'status' => 'success',
				'nom' => $compte->getNom(),
				'id' => $compte->getId()
			));
		}

		return $this->render('crm/compte/crm_compte_ajouter_modal.html.twig', array(
			'form' => $form->createView()
		));
	}

	/**
	 * @Route("/crm/compte/get-comptes-fusionner/{compte_id}", name="crm_compte_get_liste_fusionner", defaults={"compte_id" = null})
	 * @Route("/crm/compte/get-comptes-fusionner", name="crm_compte_get_liste_fusionner_default")
	 */
	public function compte_list_fusionnerAction($compte_id)
	{
		//~ echo "hich"; exit;
		$request = $this->getRequest();
		//~ var_dump($request->get('id')); exit;
		$repository = $this->getDoctrine()->getManager()->getRepository('AppBundle:CRM\Compte');
		//~ if( is_null($compte_id) )
			//~ $list = $repository->findByCompany($this->getUser()->getCompany());
		//~ else
			//~ $list = $repository->findAll($this->getUser()->getCompany(), $compte_id);
		$compte = $repository->find($request->get('id'));
		$list = $repository->findAllExcept($compte->getId());

		$res = array();
		if( count($list) > 0 )
		{
			foreach ($list as $compte) {
				$_res = array('id' => $compte->getId(), 'display' => $compte->getNom());
				$res[] = $_res;
			}
		}

		$response = new \Symfony\Component\HttpFoundation\Response(json_encode($res));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	/**
	 * @Route("/crm/compte/editer/{id}", name="crm_compte_editer")
	 */
	public function compteEditerAction(Compte $compte)
	{
		$form = $this->createForm(
				new CompteType(
						$compte->getUserGestion()->getId(),
						$this->getUser()->getCompany()->getId()
					), $compte
				);

		$form->add('addressPicker', 'text', array(
			'label' 	=> 'Veuillez renseigner l\'adresse ici',
			'mapped' 	=> false,
			'required' 	=> false
		));
		$request = $this->getRequest();
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {

			$compte->setDateEdition(new \DateTime(date('Y-m-d')));
			$compte->setUserEdition($this->getUser());
			$em = $this->getDoctrine()->getManager();
			$em->persist($compte);
			$em->flush();

			return $this->redirect($this->generateUrl(
					'crm_compte_voir',
					array('id' => $compte->getId())
			));
		}

		return $this->render('crm/compte/crm_compte_editer.html.twig', array(
				'form' => $form->createView()
		));
	}

	/**
	 * @Route("/crm/compte/supprimer/{id}", name="crm_compte_supprimer")
	 */
	public function compteSupprimerAction(Compte $compte)
	{
		$form = $this->createFormBuilder()->getForm();

		$request = $this->getRequest();
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {

			$em = $this->getDoctrine()->getManager();
			$em->remove($compte);
			$em->flush();

			return $this->redirect($this->generateUrl(
				'crm_compte_liste'
			));
		}

		return $this->render('crm/compte/crm_compte_supprimer.html.twig', array(
				'form' => $form->createView(),
				'compte' => $compte
		));
	}

	/**
	 * @Route("/crm/compte/get_coordonnees/{nom}", name="crm_compte_get_coordonnees")
	 */
	public function compteGetCoordonnees($nom)
	{
		$repository = $this->getDoctrine()->getManager()->getRepository('AppBundle:CRM\Compte');
		$compte = $repository->findOneBy(array(
			'nom' => $nom,
			'company' => $this->getUser()->getCompany()
		));
		/*
		$s_telephone = null;
		if($compte->getTelephone()){
			$s_telephone = $this->get('libphonenumber.phone_number_util')->format($compte->getTelephone(), 'INTERNATIONAL');
		}

		$s_fax = null;
		if($compte->getFax()){
			$s_fax = $this->get('libphonenumber.phone_number_util')->format($compte->getFax(), 'INTERNATIONAL');
		}
		*/
		$response = new JsonResponse();
		$response->setData(array(
    		'adresse' => $compte->getAdresse(),
			'codePostal' => $compte->getCodePostal(),
			'ville' => $compte->getVille(),
			'region' => $compte->getRegion(),
			'pays' => $compte->getPays(),
			'telephone' => $compte->getTelephone(),
			'fax' => $compte->getFax()
		));

		return $response;

	}

	/**
	 * @Route("/crm/compte/get_coordonnees_by_id/{id}", name="crm_compte_get_coordonnees_by_id")
	 */
	public function compteGetCoordonneesById(Compte $compte)
	{
		/*
		$s_telephone = null;
		if($compte->getTelephone()){
			$s_telephone = $this->get('libphonenumber.phone_number_util')->format($compte->getTelephone(), 'INTERNATIONAL');
		}

		$s_fax = null;
		if($compte->getFax()){
			$s_fax = $this->get('libphonenumber.phone_number_util')->format($compte->getFax(), 'INTERNATIONAL');
		}
		*/

		$response = new JsonResponse();
		$response->setData(array(
                'secteur' => $compte->getSecteurActivite(),
				'adresse' => $compte->getAdresse(),
				'codePostal' => $compte->getCodePostal(),
				'ville' => $compte->getVille(),
				'region' => $compte->getRegion(),
				'pays' => $compte->getPays(),
				'telephone' => $compte->getTelephone(),
				'fax' => $compte->getFax()
		));

		return $response;
	}

	/**
	 * @Route("/crm/compte/get-comptes",
	 *   name="crm_compte_get_liste",
	 *   options={"expose"=true}
	 * )
	 */
	public function compte_listAction() {

		$repository = $this->getDoctrine()->getManager()->getRepository('AppBundle:CRM\Compte');

		$list = $repository->findByCompany($this->getUser()->getCompany());

		$res = array();
		foreach ($list as $compte) {
			$_res = array('id' => $compte->getId(), 'display' => $compte->getNom());
			$res[] = $_res;
		}

		$response = new \Symfony\Component\HttpFoundation\Response(json_encode($res));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	/**
	 * @Route("/crm/compte/get-comptes-and-contacts", name="crm_compte_contacts_get_liste")
	 */
	public function compte_contacts_listAction() {

		$compteRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:CRM\Compte');
		$list = $compteRepository->findByCompany($this->getUser()->getCompany());

		$res = array();
		foreach ($list as $compte) {
			$_res = array('id' => $compte->getId(), 'display' => $compte->getNom());
			$res[] = $_res;
		}

		$contactRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:CRM\Contact');
		$list = $contactRepository->findByCompany($this->getUser()->getCompany());
		foreach ($list as $contact) {
			$_res = array('id' => $contact->getCompte()->getId(), 'display' => $contact->getCompte()->getNom().' ('.$contact.')');
			$res[] = $_res;
		}

		$response = new \Symfony\Component\HttpFoundation\Response(json_encode($res));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	/**
	 * @Route("/crm/compte/importer", name="crm_compte_importer")
	 */
	public function compteImporterAction()
	{
		$form = $this->createForm(new CompteImportType($this->getUser()->getCompany()));

		$request = $this->getRequest();
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {

			//recuperation des données du formulaire
			$data = $form->getData();
			$file = $data['file'];

			//enregistrement temporaire du fichier uploadé
			$filename = date('Ymdhms').'-'.$this->getUser()->getId().'-'.$file->getClientOriginalName();
			$path =  $this->get('kernel')->getRootDir().'/../web/upload/crm/import_comptes';
			$file->move($path, $filename);
			$session = $request->getSession();
			$session->set('import_comptes_filename', $filename);

			//creation du formulaire de mapping
			return $this->redirect($this->generateUrl('crm_compte_importer_mapping'));
		}

		return $this->render('crm/compte/crm_compte_importer.html.twig', array(
				'form' => $form->createView()
		));
	}

	/**
	 * @Route("/crm/compte/importer/mapping", name="crm_compte_importer_mapping")
	 */
	public function compteImporterMappingAction()
	{
		$request = $this->getRequest();
		$session = $request->getSession();

		//recuperation et ouverture du fichier temporaire uploadé
		$path =  $this->get('kernel')->getRootDir().'/../web/upload/crm/import_comptes';
		$filename = $session->get('import_comptes_filename');
		$fh = fopen($path.'/'.$filename, 'r+');

		//récupération de la première ligne pour créer le formulaire de mapping
		$arr_headers = array();
		$i = 0;

		while( ($row = fgetcsv($fh)) !== FALSE && $i<1 ) {
			$arr_headers = explode(';',$row[$i]);
			$i++;
		}
		$arr_headers = array_combine($arr_headers, $arr_headers); //pour que l'array ait les mêmes clés et valeurs

		// 		$col = 'A';
		// 		foreach($arr_headers as $key => $header){
		// 			$arr_headers[$key] = $header.' (col '.$col.')';
		// 			$col++;
		// 		}

		$form_mapping = $this->createForm(new CompteImportMappingType($arr_headers, $filename));
		$form_mapping->handleRequest($request);

		if ($form_mapping->isSubmitted() && $form_mapping->isValid()) {

			$data = $form_mapping->getData();

			$arr_mapping = array();
			//recuperation des colonnes
			$arr_mapping['objet'] = $data['objet'];
			$arr_mapping['num'] = $data['num'];
			$arr_mapping['compte'] = $data['compte'];
			$arr_mapping['date'] = $data['date'];
			$arr_mapping['echeance'] = $data['echeance'];
			$arr_mapping['tva'] = $data['tva'];
			$arr_mapping['tauxTVA'] = $data['tauxTVA'];
			$arr_mapping['remise'] =$data['remise'];
			$arr_mapping['description'] = $data['description'];
			$arr_mapping['etat'] =$data['etat'];
			$arr_mapping['user'] =$data['user'];

			$arr_mapping['produitNom'] = $data['produitNom'];
			$arr_mapping['produitType'] = $data['produitType'];
			$arr_mapping['produitDescription'] = $data['produitDescription'];
			$arr_mapping['produitTarif'] = $data['produitTarif'];
			$arr_mapping['produitQuantite'] = $data['produitQuantite'];

			$session->set('import_historique_facture_arr_mapping', $arr_mapping);

			//creation du formulaire de validation
			return $this->redirect($this->generateUrl('compta_facture_importer_historique_validation'));
		}

		return $this->render('compta/facture/compta_facture_importer_historique_mapping.html.twig', array(
				'form' => $form_mapping->createView()
		));

		return 0;
	}


}
