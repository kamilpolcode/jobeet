<?php

namespace Ens\JobeetBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Ens\JobeetBundle\Entity\Job;
use Ens\JobeetBundle\Form\JobType;

use Symfony\Component\HttpFoundation\Response;

/**
 * Job controller.
 *
 */
class JobController extends Controller {

    /**
     * Lists all Job entities.
     *
     */
    public function indexAction() {
        $em = $this->getDoctrine()->getManager();

        $categories = $em->getRepository('EnsJobeetBundle:Category')->getWithJobs();


        foreach ($categories as $category) {
            $category->setActiveJobs($em->getRepository('EnsJobeetBundle:Job')->getActiveJobs($category->getId(), $this->container->getParameter('max_jobs_on_homepage')));
            $category->setMoreJobs($em->getRepository('EnsJobeetBundle:Job')->countActiveJobs($category->getId()) - $this->container->getParameter('max_jobs_on_homepage'));
        }

        return $this->render('EnsJobeetBundle:Job:index.html.twig', array(
                    'categories' => $categories
        ));
    }

    /**
     * Creates a new Job entity.
     *
     */
    public function createAction(Request $request) {
        $entity = new Job();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            //another way to handle the file upload
            //now has been changed to handle file upload by job entity
            //loot at: job.orm.yml and Job entity
            //$entity->file->move(__DIR__.'/../../../../web/uploads/jobs', $entity->file->getClientOriginalName());
            //$entity->setLogo($entity->file->getClientOriginalName());

            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('ens_job_preview', array(
                                'company' => $entity->getCompanySlug(),
                                'location' => $entity->getLocationSlug(),
                                'token' => $entity->getToken(),
                                'position' => $entity->getPositionSlug())));
        }

        return $this->render('EnsJobeetBundle:Job:new.html.twig', array(
                    'entity' => $entity,
                    'form' => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Job entity.
     *
     * @param Job $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Job $entity) {
        $form = $this->createForm(new JobType(), $entity, array(
            'action' => $this->generateUrl('ens_job_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Job entity.
     *
     */
    public function newAction() {
        $entity = new Job();
        $entity->setType('full-time');
        $form = $this->createCreateForm($entity);

        return $this->render('EnsJobeetBundle:Job:new.html.twig', array(
                    'entity' => $entity,
                    'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Job entity.
     *
     */
    public function showAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('EnsJobeetBundle:Job')->getActiveJob($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }

        $deleteForm = $this->createDeleteForm($entity->getToken());

        return $this->render('EnsJobeetBundle:Job:show.html.twig', array(
                    'entity' => $entity,
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Job entity.
     *
     */
    public function editAction($token) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($token);
        $publishForm = $this->createPublishForm($token);
                
        return $this->render('EnsJobeetBundle:Job:edit.html.twig', array(
                    'entity' => $entity,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
                    'publish_form' => $publishForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a Job entity.
     *
     * @param Job $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Job $entity) {
        $form = $this->createForm(new JobType(), $entity, array(
            'action' => $this->generateUrl('ens_job_update', array('token' => $entity->getToken())),
        ));

        $form->add('submit', 'submit', array('label' => 'Update'))->add('token', 'hidden');

        return $form;
    }

    /**
     * Edits an existing Job entity.
     * Processes the form (validation, form repopulation) and updates an existing job with the user submitted values
     *
     */
    public function updateAction($token) {
        $em = $this->getDoctrine()->getManager();
        $request = $this->getRequest();

        $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }

        $deleteForm = $this->createDeleteForm($token);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        $publishForm = $this->createPublishForm($entity->getToken());

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('ens_job_preview', array(
                                'company' => $entity->getCompanySlug(),
                                'location' => $entity->getLocationSlug(),
                                'token' => $entity->getToken(),
                                'position' => $entity->getPositionSlug()
            )));
        }

        return $this->render('EnsJobeetBundle:Job:edit.html.twig', array(
                    'entity' => $entity,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
                    'publish_form' => $publishForm->createView(),
        ));
    }

    /**
     * Deletes a Job entity.
     *
     */
    public function deleteAction($token) {
        $form = $this->createDeleteForm($token);
        $request = $this->getRequest();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);
              
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Job entity.');
            }

            $em->remove($entity);
            $em->flush();
        }
     
        return $this->redirect($this->generateUrl('ens_job'));
    }

    /**
     * Creates a form to delete a Job entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($token) {

        return $this->createFormBuilder(array('token' => $token))
                        ->add('token', 'hidden')
                        ->getForm()
        ;
    }

    public function previewAction($token) {
        $em = $this->getDoctrine()->getEntityManager();

        $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }

        $deleteForm = $this->createDeleteForm($entity->getToken());
        $publishForm = $this->createPublishForm($entity->getToken());

        return $this->render('EnsJobeetBundle:Job:show.html.twig', array(
                    'entity' => $entity,
                    'delete_form' => $deleteForm->createView(),
                    'publish_form' => $publishForm->createView(),
        ));
    }


    public function publishAction($token) {
        $form = $this->createPublishForm($token);
        $request = $this->getRequest();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Job entity.');
            }

            $entity->publish();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->set('notice', 'Your job is now online for 30 days.');
        }

        return $this->redirect($this->generateUrl('ens_job_preview', array(
                            'company' => $entity->getCompanySlug(),
                            'location' => $entity->getLocationSlug(),
                            'token' => $entity->getToken(),
                            'position' => $entity->getPositionSlug()
        )));
    }

    private function createPublishForm($token) {
        return $this->createFormBuilder(array('token' => $token))
                        ->add('token', 'hidden')
                        ->getForm()
        ;
    }

}
