<?php

namespace App\Form;

use App\Entity\Product;
use App\Enum\CategoryEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'required' => true,
                'label_attr' => ['class' => 'form-label required'],
            ])
            ->add('shortDescription', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'label_attr' => ['class' => 'form-label required'],
            ])
            ->add('priceExclVat', MoneyType::class, [
                'label' => 'Price (excl. VAT)',
                'currency' => 'EUR',
                'required' => true,
                'label_attr' => ['class' => 'form-label required'],
            ])
            ->add('category', EnumType::class, [
                'class' => CategoryEnum::class,
                'label' => 'Category',
                'required' => true,
                'label_attr' => ['class' => 'form-label required'],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Product Image',
                'mapped' => true,
                'required' => false,
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantity',
                'required' => true,
                'label_attr' => ['class' => 'form-label required'],
            ])
            ->add('sku', TextType::class, [
                'required' => false,
                'disabled' => true,
                'help' => 'SKU will be generated automatically',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
