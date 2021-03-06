<?php

namespace AppBundle\Entity\Emailing;

use Doctrine\ORM\Mapping as ORM;

/**
 * Campagne
 *
 * @ORM\Table(name="campagne")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Emailing\CampagneRepository")
 */
class Campagne
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_campagne", type="string", length=50)
     */
    private $nomCampagne;

    /**
     * @var string
     *
     * @ORM\Column(name="objet_email", type="string", length=255)
     */
    private $objetEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_expediteur", type="string", length=100)
     */
    private $nomExpediteur;

    /**
     * @var string
     *
     * @ORM\Column(name="email_expediteur", type="string", length=100)
     */
    private $emailExpediteur;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_creation", type="date")
     */
    private $dateCreation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_edition", type="date", nullable=true)
     */
    private $dateEdition;


    /**
     * @var boolean
     *
     * @ORM\Column(name="envoyee", type="boolean")
     */
    private $envoyee = false;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_envoi", type="datetime", nullable=true)
     */
    private $dateEnvoi;


    /**
     * @var string
     *
     * @ORM\Column(name="template", type="text", nullable=true)
     */
    private $template;


    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\CRM\Rapport", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $listesContact;
    
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $userCreation;
    
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(nullable=true)
     */
    private $userEdition;
    
    /**
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Emailing\ZoneContent", mappedBy="campagne", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     */
    private $zoneContents;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set nomCampagne
     *
     * @param string $nomCampagne
     * @return Campagne
     */
    public function setNomCampagne($nomCampagne)
    {
        $this->nomCampagne = $nomCampagne;

        return $this;
    }

    /**
     * Get nomCampagne
     *
     * @return string 
     */
    public function getNomCampagne()
    {
        return $this->nomCampagne;
    }

    /**
     * Set objetEmail
     *
     * @param string $objetEmail
     * @return Campagne
     */
    public function setObjetEmail($objetEmail)
    {
        $this->objetEmail = $objetEmail;

        return $this;
    }

    /**
     * Get objetEmail
     *
     * @return string 
     */
    public function getObjetEmail()
    {
        return $this->objetEmail;
    }

    /**
     * Set nomExpediteur
     *
     * @param string $nomExpediteur
     * @return Campagne
     */
    public function setNomExpediteur($nomExpediteur)
    {
        $this->nomExpediteur = $nomExpediteur;

        return $this;
    }

    /**
     * Get nomExpediteur
     *
     * @return string 
     */
    public function getNomExpediteur()
    {
        return $this->nomExpediteur;
    }

    /**
     * Set emailExpediteur
     *
     * @param string $emailExpediteur
     * @return Campagne
     */
    public function setEmailExpediteur($emailExpediteur)
    {
        $this->emailExpediteur = $emailExpediteur;

        return $this;
    }

    /**
     * Get emailExpediteur
     *
     * @return string 
     */
    public function getEmailExpediteur()
    {
        return $this->emailExpediteur;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     * @return Campagne
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation
     *
     * @return \DateTime 
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * Set dateEdition
     *
     * @param \DateTime $dateEdition
     * @return Campagne
     */
    public function setDateEdition($dateEdition)
    {
        $this->dateEdition = $dateEdition;

        return $this;
    }

    /**
     * Get dateEdition
     *
     * @return \DateTime 
     */
    public function getDateEdition()
    {
        return $this->dateEdition;
    }

    /**
     * Set dateEnvoi
     *
     * @param \DateTime $dateEnvoi
     * @return Campagne
     */
    public function setDateEnvoi($dateEnvoi)
    {
        $this->dateEnvoi = $dateEnvoi;

        return $this;
    }

    /**
     * Get dateEnvoi
     *
     * @return \DateTime 
     */
    public function getDateEnvoi()
    {
        return $this->dateEnvoi;
    }

    /**
     * Set envoyee
     *
     * @param boolean $envoyee
     * @return Contact
     */
    public function setEnvoyee($envoyee)
    {
        $this->envoyee = $envoyee;

        return $this;
    }

    /**
     * Get envoyee
     *
     * @return boolean 
     */
    public function getEnvoyee()
    {
        return $this->envoyee;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return Campagne
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return string 
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set userCreation
     *
     * @param \AppBundle\Entity\User $userCreation
     * @return Campagne
     */
    public function setUserCreation(\AppBundle\Entity\User $userCreation)
    {
        $this->userCreation = $userCreation;

        return $this;
    }

    /**
     * Get userCreation
     *
     * @return \AppBundle\Entity\User 
     */
    public function getUserCreation()
    {
        return $this->userCreation;
    }

    /**
     * Set userEdition
     *
     * @param \AppBundle\Entity\User $userEdition
     * @return Campagne
     */
    public function setUserEdition(\AppBundle\Entity\User $userEdition = null)
    {
        $this->userEdition = $userEdition;

        return $this;
    }

    /**
     * Get userEdition
     *
     * @return \AppBundle\Entity\User 
     */
    public function getUserEdition()
    {
        return $this->userEdition;
    }

    /**
     * Add zoneContents
     *
     * @param \AppBundle\Entity\Emailing\ZoneContent $zoneContents
     * @return Campagne
     */
    public function addZoneContent(\AppBundle\Entity\Emailing\ZoneContent $zoneContents)
    {
        $this->zoneContents[] = $zoneContents;

        return $this;
    }

    /**
     * Remove zoneContents
     *
     * @param \AppBundle\Entity\Emailing\ZoneContent $zoneContents
     */
    public function removeZoneContent(\AppBundle\Entity\Emailing\ZoneContent $zoneContents)
    {
        $this->zoneContents->removeElement($zoneContents);
    }

    /**
     * Get zoneContents
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getZoneContents()
    {
         $criteria = Criteria::create()
	      ->orderBy(array("date" => Criteria::DESC));
	
	    return $this->zoneContents->matching($criteria);
    }

    /**
     * Add listes
     *
     * @param \AppBundle\Entity\CRM\Rapport $listesContact
     * @return Contact
     */
    public function addListeContact(\AppBundle\Entity\CRM\Rapport $listesContact)
    {
    	$found = false;

    	if( isset($this->listesContact) ){
			foreach($this->listesContact as $old_listeContact){
				if($old_listeContact->getId() == $listesContact->getId()){
					$found = true;
				}
			}
    	}
    	//~ else{
			//~ $this->listeContact = array();
		//~ }
    	if(!$found){
       		$this->listesContact[] = $listesContact;
    	}
        return $this;
    }

    /**
     * Remove all listes
     *
     * @param \AppBundle\Entity\CRM\Rapport $listesContact
     */
    public function removeListesContact()
    {
        $this->listesContact = null;
    }
    
    /**
     * Remove liste
     *
     * @param \AppBundle\Entity\CRM\Rapport $listesContact
     */
    public function removeListeContact(\AppBundle\Entity\CRM\Rapport $listesContact)
    {
    	$this->listesContact->removeElement($listesContact);
    }

    /**
     * Get listesContact
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getListesContact()
    {
        return $this->listesContact;
    }

}
