<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ValidatorManager;
use App\Entity\Reino;

/**
 * @Route("/api/especie/cladograma/reino", name="especie_", format="json")
 */
class ReinoController extends AbstractController
{
    private $validatorManager;
    
    public function __construct(ValidatorManager $validatorManager) 
    {
        $this->validatorManager = $validatorManager;
    }

    /**
     * @Route("/criar-reino", name="criar_reino", methods="POST")
     */
    public function criarReino(Request $request): Response
    {  
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = $request->request->all();

        $input = [
            'nome' => empty($data['nome']) ? '' : $data['nome'],
        ];

        $entity = new Reino;

        $entity->setNome($input['nome']);

        $errors = $this->validatorManager->validate($entity);

        if ($this->validatorManager->hasError($errors)) {
            return $this->validatorManager->response();
        }

        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->persist($entity);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $entity->getId(),
            'nome' => $entity->getNome(),
        ]);
    }

    /**
     * @Route("/buscar-reino/{nome}", name="buscar_reino", methods="GET")
     */
    public function buscarReino(Request $request, $nome = ''): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if (empty($nome)) {
            return new JsonResponse([]);
        }
        
        $entityManager = $this->getDoctrine()->getManager();

        $query = $entityManager->createQuery(
            'SELECT reino 
            FROM App\Entity\Reino reino
            WHERE reino.nome 
            LIKE :nome'
        )->setParameter('nome', $nome . '%');

        if (!count($query->getResult())) {
            return new JsonResponse([]);
        }

        foreach ($query->getResult() as $entity) {
            $entities[] = [
                'id' => $entity->getId(),
                'nome' => $entity->getNome(),
            ];
        }

        return new JsonResponse($entities);
    }
}
