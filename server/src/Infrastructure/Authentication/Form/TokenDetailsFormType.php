<?php declare(strict_types=1);

namespace App\Infrastructure\Authentication\Form;

use App\Domain\Authentication\Form\TokenDetailsForm;
use App\Domain\Common\SharedEntity\Token;
use App\Domain\SSLAlgorithms;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TokenDetailsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(Token::FIELD_TAGS, CollectionType::class, [
                'allow_add'    => true,
                'allow_delete' => true
            ])
            ->add(Token::FIELD_ALLOWED_MIME_TYPES, CollectionType::class, [
                'allow_add'    => true,
                'allow_delete' => true
            ])
            ->add(Token::FIELD_MAX_ALLOWED_FILE_SIZE, IntegerType::class)
            ->add(Token::FIELD_ALLOWED_IPS, CollectionType::class, [
                'allow_add'    => true,
                'allow_delete' => true
            ])
            ->add(Token::FIELD_ALLOWED_UAS, CollectionType::class, [
                'allow_add'    => true,
                'allow_delete' => true
            ])
            ->add(Token::FIELD_SECURE_COPY_ENC_KEY, TextType::class, [
                'required'   => false,
                'empty_data' => ''
            ])
            ->add(Token::FIELD_SECURE_COPY_ENC_METHOD, ChoiceType::class, [
                'choices'    => SSLAlgorithms::ALGORITHMS
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        return $resolver->setDefaults([
            'data_class'           => TokenDetailsForm::class,
            'csrf_protection'      => false,
            'extra_fields_message' => 'This request does not support extra parameters such as {{ extra_fields }}'
        ]);
    }
}
