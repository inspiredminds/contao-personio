<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoPersonio\Controller\ContentElement;

use Codefog\HasteBundle\Form\Form;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use Contao\UploadableWidgetInterface;
use Contao\Widget;
use InspiredMinds\ContaoPersonio\Event\ModifyApplicationFormEvent;
use InspiredMinds\ContaoPersonio\Exception\PersonioApiException;
use InspiredMinds\ContaoPersonio\Message\PersonioApplicationMessage;
use InspiredMinds\ContaoPersonio\Model\Job;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsContentElement(self::TYPE, template: 'ce_personio_job_application')]
class PersonioJobApplicationController extends AbstractContentElementController
{
    public const TYPE = 'personio_job_application';

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly TranslatorInterface $translator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly KernelInterface $kernel,
        private readonly string $projectDir,
    ) {
    }

    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        if (!($job = $request->attributes->get('_content')) instanceof Job) {
            return new Response();
        }

        $form = $this->buildForm($model, $request);

        if ($form->validate()) {
            $files = [];

            $data = $form->fetchAll(
                function (string $name, Widget $widget) use (&$files): string|null {
                    if (!$widget->submitInput()) {
                        return null;
                    }

                    if ($widget instanceof UploadableWidgetInterface) {
                        if (!$widget->value) {
                            return null;
                        }

                        if (\is_array($widget->value)) {
                            $first = reset($widget->value);

                            if (\is_string($first)) {
                                // Array of relative paths
                                $files[$name] = array_map(fn (string $value): string => Path::join($this->projectDir, $value), (array) $widget->value);
                            } else {
                                // Array of upload informations
                                $files[$name] = array_column($widget->value, 'tmp_name');
                            }
                        } else {
                            $files[$name] = Path::join($this->projectDir, (string) $widget->value);
                        }

                        return null;
                    }

                    if (\is_array($widget->value)) {
                        $data[$name] = implode(',', $widget->value);
                    } else {
                        $data[$name] = $widget->value;
                    }

                    return $widget->value;
                },
            );

            $data['job_position_id'] = (int) $job->id;
            $data['application_date'] = (new \DateTimeImmutable())->format('Y-m-d');

            try {
                $this->messageBus->dispatch(new PersonioApplicationMessage($data, $files));

                if ($jumpTo = PageModel::findById($model->jumpTo)) {
                    return new RedirectResponse($jumpTo->getAbsoluteUrl());
                }

                return new RedirectResponse($request->getUri());
            } catch (\Throwable $e) {
                if ($this->kernel->isDebug()) {
                    throw $e;
                }

                do {
                    if ($e instanceof PersonioApiException) {
                        if ($e->getMessage() !== ($translated = $this->translator->trans($e->getMessage(), [], 'personio'))) {
                            $template->error = $translated;
                        } else {
                            $template->error = $this->translator->trans('ERR.general', [], 'contao_default');
                        }

                        break;
                    }
                } while ($e = $e->getPrevious());

                if (!$template->error) {
                    throw $e;
                }
            }
        }

        $template->form = $form->generate();

        return $template->getResponse();
    }

    private function buildForm(ContentModel $model, Request $request): Form
    {
        $fields = StringUtil::deserialize($model->personio_applicationFields, true);

        $form = new Form(
            'personio-job-application-'.$model->id,
            'POST',
            static fn (): bool => 'personio-job-application-'.$model->id === $request->request->get('FORM_SUBMIT'),
        );

        $fileConfig = [
            'multiple' => true,
            'uploaderLimit' => 10,
            'doNotOverwrite' => true,
            'extensions' => 'pdf,pptx,xlsx,docx,doc,xls,ppt,ods,odt,7z,gz,rar,zip,bmp,gif,jpg,png,tif,csv,txt,rtf,mp4,3gp,mov,avi,wmv',
            'maxlength' => 20971520,
            'class' => 'widget-cv',
        ];

        foreach ($fields as $field) {
            match ($field) {
                // Standard fields
                'first_name' => (
                    function (Form $form): void {
                        $form->addFormField('first_name', [
                            'label' => $this->translator->trans('MSC.personioFields.first_name', [], 'contao_default'),
                            'inputType' => 'text',
                            'eval' => ['mandatory' => true, 'maxlength' => 255, 'class' => 'widget-first_name'],
                        ]);
                    }
                )($form),
                'last_name' => (
                    function (Form $form): void {
                        $form->addFormField('last_name', [
                            'label' => $this->translator->trans('MSC.personioFields.last_name', [], 'contao_default'),
                            'inputType' => 'text',
                            'eval' => ['mandatory' => true, 'maxlength' => 255, 'class' => 'widget-last_name'],
                        ]);
                    }
                )($form),
                'email' => (
                    function (Form $form): void {
                        $form->addFormField('email', [
                            'label' => $this->translator->trans('MSC.personioFields.email', [], 'contao_default'),
                            'inputType' => 'text',
                            'eval' => ['mandatory' => true, 'rgxp' => 'email', 'maxlength' => 255, 'class' => 'widget-email'],
                        ]);
                    }
                )($form),
                'message' => (
                    function (Form $form): void {
                        $form->addFormField('message', [
                            'label' => $this->translator->trans('MSC.personioFields.message', [], 'contao_default'),
                            'inputType' => 'textarea',
                            'eval' => ['class' => 'widget-message', 'maxlength' => 255],
                        ]);
                    }
                )($form),
                // System attributes
                'birthday' => (
                    function (Form $form): void {
                        $form->addFormField('birthday', [
                            'label' => $this->translator->trans('MSC.personioFields.birthday', [], 'contao_default'),
                            'inputType' => 'text',
                            'eval' => ['rgxp' => 'custom', 'customRgxp' => '/^[0-9]{4}-[0-0]{2}-[0-0]{2}$/', 'class' => 'widget-birthday', 'errorMsg' => $this->translator->trans('birthdayError', [], 'personio'), 'maxlength' => 10],
                        ]);
                    }
                )($form),
                'gender' => (
                    function (Form $form): void {
                        $form->addFormField('gender', [
                            'label' => $this->translator->trans('MSC.personioFields.gender', [], 'contao_default'),
                            'inputType' => 'select',
                            'options' => [
                                'male',
                                'female',
                                'diverse',
                                'undefined',
                            ],
                            'reference' => &$GLOBALS['TL_LANG']['MSC']['personioGenderOptions'],
                            'eval' => ['class' => 'widget-gender', 'includeBlankOption' => true],
                        ]);
                    }
                )($form),
                'location' => (
                    function (Form $form): void {
                        $form->addFormField('location', [
                            'label' => $this->translator->trans('MSC.personioFields.location', [], 'contao_default'),
                            'inputType' => 'text',
                            'eval' => ['class' => 'widget-location', 'maxlength' => 255],
                        ]);
                    }
                )($form),
                'phone' => (
                    function (Form $form): void {
                        $form->addFormField('phone', [
                            'label' => $this->translator->trans('MSC.personioFields.phone', [], 'contao_default'),
                            'inputType' => 'text',
                            'eval' => ['class' => 'widget-phone', 'maxlength' => 255],
                        ]);
                    }
                )($form),
                'available_from' => (
                    function (Form $form): void {
                        $form->addFormField('available_from', [
                            'label' => $this->translator->trans('MSC.personioFields.available_from', [], 'contao_default'),
                            'inputType' => 'text',
                            'eval' => ['class' => 'widget-available_from', 'maxlength' => 255],
                        ]);
                    }
                )($form),
                'salary_expectations' => (
                    function (Form $form): void {
                        $form->addFormField('salary_expectations', [
                            'label' => $this->translator->trans('MSC.personioFields.salary_expectations', [], 'contao_default'),
                            'inputType' => 'text',
                            'eval' => ['class' => 'widget-salary_expectations', 'maxlength' => 255],
                        ]);
                    }
                )($form),
                // Files
                'cv' => (
                    function (Form $form, array $fileConfig): void {
                        $form->addFormField('cv', [
                            'label' => $this->translator->trans('MSC.personioFields.cv', [], 'contao_default'),
                            'inputType' => 'fineUploader',
                            'eval' => $fileConfig,
                        ]);
                    }
                )($form, $fileConfig),
                'cover-letter' => (
                    function (Form $form, array $fileConfig): void {
                        $form->addFormField('cover-letter', [
                            'label' => $this->translator->trans('MSC.personioFields.cover-letter', [], 'contao_default'),
                            'inputType' => 'fineUploader',
                            'eval' => $fileConfig,
                        ]);
                    }
                )($form, $fileConfig),
                'employment-reference' => (
                    function (Form $form, array $fileConfig): void {
                        $form->addFormField('employment-reference', [
                            'label' => $this->translator->trans('MSC.personioFields.employment-reference', [], 'contao_default'),
                            'inputType' => 'fineUploader',
                            'eval' => $fileConfig,
                        ]);
                    }
                )($form, $fileConfig),
                'certificate' => (
                    function (Form $form, array $fileConfig): void {
                        $form->addFormField('certificate', [
                            'label' => $this->translator->trans('MSC.personioFields.certificate', [], 'contao_default'),
                            'inputType' => 'fineUploader',
                            'eval' => $fileConfig,
                        ]);
                    }
                )($form, $fileConfig),
                'work-sample' => (
                    function (Form $form, array $fileConfig): void {
                        $form->addFormField('work-sample', [
                            'label' => $this->translator->trans('MSC.personioFields.work-sample', [], 'contao_default'),
                            'inputType' => 'fineUploader',
                            'eval' => $fileConfig,
                        ]);
                    }
                )($form, $fileConfig),
                'other' => (
                    function (Form $form, array $fileConfig): void {
                        $form->addFormField('other', [
                            'label' => $this->translator->trans('MSC.personioFields.other', [], 'contao_default'),
                            'inputType' => 'fineUploader',
                            'eval' => $fileConfig,
                        ]);
                    }
                )($form, $fileConfig),
            };
        }

        $form->addSubmitFormField($this->translator->trans('submit', [], 'personio'));

        $this->eventDispatcher->dispatch(new ModifyApplicationFormEvent($form, $model));

        return $form;
    }
}
