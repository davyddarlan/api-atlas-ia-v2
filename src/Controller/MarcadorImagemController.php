<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\MarcadorImagem;
use App\Entity\Multimidia;
use App\Service\ValidatorManager;

/**
 * @Route("/api/especie/marcador-imagem", name="especie_marcador_imagem_", format="json")
 */
class MarcadorImagemController extends AbstractController
{
    private $validatorManager;
    
    public function __construct(ValidatorManager $validatorManager)  
    {
        $this->validatorManager = $validatorManager;
    }
    
    /**
     * @Route("/criar-marcador", name="criar_marcador", methods="POST")
     */
    public function criarMarcador(Request $request) {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = $request->request->all();

        $input = [
            'nome' => empty($data['nome']) ? '' : $data['nome'],
            'cor' => empty($data['cor']) ? null : $data['cor'],
        ];

        $marcadorImagem = new MarcadorImagem;

        $marcadorImagem->setNome($input['nome']);
        $marcadorImagem->setCor($input['cor']);

        $errors = $this->validatorManager->validate($marcadorImagem);

        if ($this->validatorManager->hasError()) {
            return $this->validatorManager->response();
        }

        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->persist($marcadorImagem);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $marcadorImagem->getId(),
            'nome' => $marcadorImagem->getNome(),
        ]);
    }

    /**
     * @Route("/buscar-marcador/{nome}", name="buscar_marcador", methods="GET")
     */
    public function buscarMarcador($nome = null)
    {        
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if (empty($nome)) {
            return new JsonResponse([]);
        }
        
        $entityManager = $this->getDoctrine()->getManager();

        $query = $entityManager->createQuery(
            'SELECT marcador 
            FROM App\Entity\MarcadorImagem marcador
            WHERE marcador.nome 
            LIKE :nome'
        )->setParameter('nome', $nome . '%');

        if (!count($query->getResult())) {
            return new JsonResponse([]);
        }

        foreach ($query->getResult() as $marcador) {
            $marcadores[] = [
                'id' => $marcador->getId(),
                'nome' => $marcador->getNome(),
            ];
        }

        return new JsonResponse($marcadores);
    }

    /**
     * @Route("/associar-marcador", name="associar_marcador", methods="POST")
     */
    public function associarMarcador(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = $request->request->all();

        $input = [
            'marcador_id' => empty($data['marcador_id']) ? null : $data['marcador_id'],
            'multimidia_id' => empty($data['multimidia_id']) ? null : $data['multimidia_id'],
        ];

        $entityManager = $this->getDoctrine()->getManager();

        $multimidia = $entityManager->getRepository(Multimidia::class)->find($input['multimidia_id']);
        $marcador = $entityManager->getRepository(MarcadorImagem::class)->find($input['marcador_id']);

        if (!$multimidia || !$marcador) {
            throw $this->createNotFoundException('The entity was not found');
        }

        $marcador->addMultimidium($multimidia);
        
        $entityManager->persist($marcador);
        $entityManager->flush();

        return new JsonResponse($input);
    }
}
