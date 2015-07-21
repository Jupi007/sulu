<?php

namespace Sulu\Bundle\SnippetBundle\Form;

use Sulu\Bundle\ContentBundle\Form\Type\AbstractStructureBehaviorType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SnippetType extends AbstractStructureBehaviorType
{
    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        parent::setDefaultOptions($options);

        $options->setDefaults(array(
            'data_class' => 'Sulu\Bundle\SnippetBundle\Document\SnippetDocument',
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('workflowStage');

        // TODO: Fix the admin interface to not send this junk (not required for snippets)
        $builder->add('redirectType', 'text', array('mapped' => false));
        $builder->add('resourceSegment', 'text', array('mapped' => false));
        $builder->add('navigationContexts', 'text', array('mapped' => false));
        $builder->add('shadowLocaleEnabled', 'text', array('mapped' => false));
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'snippet';
    }
}
