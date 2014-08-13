<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Contact;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityNotFoundException;
use Sulu\Bundle\ContactBundle\Api\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress as ContactAddressEntity;
use Sulu\Bundle\ContactBundle\Entity\Address as AddressEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress as ContactAddressEntity;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;

/**
 * This Manager handles Contact functionality
 * Class ContactManager
 *
 * @package Sulu\Bundle\ContactBundle\Contact
 */
class ContactManager extends AbstractContactManager
{
    protected $contactEntity = 'SuluContactBundle:Contact';
    protected $tagManager;

    function __construct(ObjectManager $em, TagmanagerInterface $tagManager)
    {
        parent::__construct($em);
        $this->tagManager = $tagManager;
    }

    /**
     * adds an address to the entity
     *
     * @param Contact $contact The entity to add the address to
     * @param AddressEntity $address The address to be added
     * @param Bool $isMain Defines if the address is the main Address of the contact
     * @return ContactAddressEntity
     * @throws \Exception
     */
    public function addAddress($contact, AddressEntity $address, $isMain)
    {
        if (!$contact || !$address) {
            throw new \Exception('Contact and Address cannot be null');
        }
        $contactAddress = new ContactAddressEntity();
        $contactAddress->setContact($contact);
        $contactAddress->setAddress($address);
        if ($isMain) {
            $this->unsetMain($contact->getContactAddresses());
        }
        $contactAddress->setMain($isMain);
        $this->em->persist($contactAddress);

        $contact->addContactAddresse($contactAddress);

        return $contactAddress;
    }

    /**
     * removes the address relation from a contact and also deletes the address if it has no more relations
     *
     * @param ContactEntity $contact
     * @param ContactAddressEntity $contactAddress
     * @return mixed|void
     * @throws \Exception
     */
    public function removeAddressRelation($contact, $contactAddress)
    {
        if (!$contact || !$contactAddress) {
            throw new \Exception('Contact and ContactAddress cannot be null');
        }

        // reload address to get all data (including relational data)
        /** @var AddressEntity $address */
        $address = $contactAddress->getAddress();
        $address = $this->em->getRepository(
            'SuluContactBundle:Address'
        )->findById($address->getId());

        $isMain = $contactAddress->getMain();

        // remove relation
        $contact->removeContactAddresse($contactAddress);
        $address->removeContactAddresse($contactAddress);

        // if was main, set a new one
        if ($isMain) {
            $this->setMainForCollection($contact->getContactAddresses());
        }

        // delete address if it has no more relations
        if (!$address->hasRelations()) {
            $this->em->remove($address);
        }

        $this->em->remove($contactAddress);
    }

    /**
     * Returns a collection of relations to get addresses
     *
     * @param $entity
     * @return mixed
     */
    public function getAddressRelations($entity)
    {
        return $entity->getContactAddresses();
    }

    /**
     * @param $id
     * @param $locale
     * @throws \Doctrine\ORM\EntityNotFoundException
     * @return mixed
     */
    protected function getById($id, $locale)
    {
        $contact = $this->em->getRepository($this->contactEntity)->findAccountById($id);
        if(!$contact){
            throw new EntityNotFoundException($this->contactEntity, $id);
        }
        return new Contact($contact, $locale, $this->tagManager);
    }

    /**
     * Returns all api entities
     *
     * @param $locale
     * @return mixed
     */
    protected function getAll($locale)
    {
        $contacts = [];
        $contactsEntities = $this->em->getRepository($this->contactEntity)->findAll();
        foreach($contactsEntities as $contact){
            $contacts[] = new Contact($contact, $locale, $this->tagManager);
        }
        return $contacts;
    }
}
