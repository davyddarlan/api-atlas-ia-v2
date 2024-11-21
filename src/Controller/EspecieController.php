<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Especie;
use App\Entity\NomePopular;
use App\Entity\Descobridor;
use App\Entity\Marcador;
use App\Entity\Cladograma;
use Symfony\Component\Uid\Uuid;
use \Exception;
use App\Service\ValidatorManager;
use Symfony\Component\Filesystem\Filesystem;
use App\Entity\Ordem;
use App\Entity\Familia;
use App\Entity\SubFamilia;
use App\Entity\EstadoConservacao;
use function Symfony\Component\String\u;

/**
 * @Route("/api/especie", name="especie_", format="json")
 */
class EspecieController extends AbstractController
{        
    private $validatorManager;

    public function __construct(ValidatorManager $validatorManager) 
    {
        $this->validatorManager = $validatorManager;
    }

    /**
     * @Route("/public/verificar-especie/{uuid}", name="verificar_especie", methods="GET")
     */
    public function verificarEspecie($uuid): Response
    {
        $especie = $this->getDoctrine()->getRepository(Especie::class)->findOneBy(['uuid' => $uuid]);

        if (!$especie) {
            throw $this->createNotFoundException(
                'The entity was not found.'
            );
        }
        
        return new JsonResponse([
            'nome' => $especie->getNomeCientifico(),
        ]);
    }

    /**
     * @Route("/public/listar-especie-marcador", name="listar_especie_marcador", methods="GET")
     */
    public function ListarEspecieMarcador(Request $request): Response
    {
        $name = $request->query->get('nome');

        if (empty($name)) {
            return new JsonResponse([]);
        }

        $conn = $this->getDoctrine()->getManager()->getConnection();

        $sql = '
            SELECT e.uuid, e.nome_cientifico, e.principal_nome_popular, e.capa  
            FROM especie AS e INNER JOIN marcador AS m INNER JOIN especie_marcador AS em 
            ON e.id = em.especie_id AND m.id = em.marcador_id AND m.nome = :name;
        ';

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(['name' => $name]);

        $results = [];

        foreach ($resultSet->fetchAllAssociative() as $resultSetData) {
            $results[] = [
                'uuid' => $resultSetData['uuid'],
                'nome_popular' => $resultSetData['principal_nome_popular'],
                'nome_cientifico' => $resultSetData['nome_cientifico'],
                'capa' => $resultSetData['capa'],
            ];
        }

        return new JsonResponse($results);
    }
    
    /**
     * @Route("/public/buscar-especie", name="buscar_especie", methods="GET")
     */
    public function buscarEspecie(Request $request): Response
    {
        $data = $request->query->all();

        $input = [
            'nome_especie' => empty($data['nome_especie']) ? null : $data['nome_especie'],
        ];

        if (empty($input['nome_especie'])) {
            return new JsonResponse([]);
        }

        $conn = $this->getDoctrine()->getManager()->getConnection();

        $sql = '
            SELECT DISTINCT especie.nome_cientifico, especie.uuid, especie.descricao, especie.principal_nome_popular FROM especie LEFT JOIN especie_nome_popular 
            ON especie.id = especie_nome_popular.especie_id LEFT JOIN nome_popular 
            ON especie_nome_popular.nome_popular_id = nome_popular.id WHERE especie.nome_cientifico 
            LIKE :nome OR nome_popular.nome LIKE :nome LIMIT 15;
        ';

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(['nome' => $input['nome_especie'] . '%']);

        $results = [];

        foreach ($resultSet->fetchAllAssociative() as $resultSetData) {    
            $results[] = [
                'nome_cientifico' => $resultSetData['nome_cientifico'],
                'uuid' => $resultSetData['uuid'],
                'descricao' => empty($resultSetData['descricao']) ? '' : $resultSetData['descricao'],
                'nome_popular' => empty($resultSetData['principal_nome_popular']) ? '' : $resultSetData['principal_nome_popular'],
            ];
        }

        return new JsonResponse($results);
    }
    
    /**
     * @Route("/criar-especie", name="criar_especie", methods="POST")
     */
    public function criarEspecie(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = $request->request->all();
        $entityManager = $this->getDoctrine()->getManager();
        $entityListValidator = [];

        $input = [
            'nome_cientifico' => empty($data['nome_cientifico']) ? '' : $data['nome_cientifico'],
            'nome_popular' => empty($data['nome_popular']) ? null : $data['nome_popular'],
            'nome_ingles' => empty($data['nome_ingles']) ? null : $data['nome_ingles'],
            'nome_descobridor' => empty($data['nome_descobridor']) ? null : $data['nome_descobridor'],
            'ano_descoberta' => empty($data['ano_descoberta']) ? null : $data['ano_descoberta'],
            'descricao' => empty($data['descricao']) ? null : $data['descricao'], 
        ];
        
        $especie = new Especie;

        if (!empty($input['nome_popular'])) {
            $nomePopular = new NomePopular;
            $nomePopular->setNome($input['nome_popular']);

            $especie->addNomePopular($nomePopular);

            $entityListValidator[] = $nomePopular;

            $entityManager->persist($nomePopular);
        }

        if (!empty($input['nome_descobridor'])) {
            $descobridor = new Descobridor;
            $descobridor->setNome($input['nome_descobridor']);
            
            $especie->addDescobridor($descobridor);

            $entityListValidator[] = $descobridor;

            $entityManager->persist($descobridor);
        }

        $especie->setUuid(Uuid::v4());
        $especie->setNomeCientifico($input['nome_cientifico']);
        $especie->setPrincipalNomePopular($input['nome_popular']);
        $especie->setNomeIngles($input['nome_ingles']);
        $especie->setAnoDescoberta($input['ano_descoberta']);
        $especie->setDescricao($input['descricao']);
        $especie->setCladograma(new Cladograma);

        $entityListValidator[] = $especie;

        $errors = $this->validatorManager->validate($entityListValidator);

        if ($this->validatorManager->hasError($errors)) {
            return $this->validatorManager->response();
        }

        $entityManager->persist($especie);
        $entityManager->flush();
        
        return new JsonResponse([
            'uuid' => $especie->getUuid(),
            'nome_cientifico' => $especie->getNomeCientifico(),
        ]);
    }

    /**
     * @Route("/public/exibir-principais-dados/{uuid}", name="exibir_principais_dados", methods="GET")
     */
    public function exibirPrincipaisDados($uuid): Response
    {
        $especie = $this->getDoctrine()->getRepository(Especie::class)->findOneBy(['uuid' => $uuid]);

        $output = [
            'nome_popular' => empty($especie->getPrincipalNomePopular()) ? '' : $especie->getPrincipalNomePopular(),
            'nome_cientifico' => empty($especie->getNomeCientifico()) ? '' : $especie->getNomeCientifico(),
            'descricao' => empty($especie->getDescricao()) ? '' : $especie->getDescricao(),
            'capa' => empty($especie->getCapa()) ? '' : $especie->getCapa(),
        ];

        return new JsonResponse($output);
    }

    /**
     * @Route("/remover-capa", name="remover_capa", methods="DELETE")
     */
    public function removerCapa(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->getConnection()->beginTransaction();
        
        try {
            $uuid = $request->query->get('uuid');

            if (empty($uuid)) {
                throw new Exception('The entity was not found.');
            }

            $especie = $entityManager->getRepository(Especie::class)->findOneBy(['uuid' => $uuid]);

            if (!$especie) {
                throw new Exception('The entity was not found.');
            }

            $file = $this->getParameter('public_directory_capas') . '/' . $especie->getCapa();
            $especie->setCapa(null);
            
            $filesystem = new Filesystem();
            $filesystem->remove($file);

            $entityManager->flush();

            $entityManager->getConnection()->commit();

            return new JsonResponse([
                'uuid' => $especie->getUuid(),
            ]);
        } catch (Exception $e) {
            $entityManager->getConnection()->rollBack();

            throw $this->createNotFoundException($e->getMessage());
        }
    }

    /**
     * @Route("/adicionar-capa", name="adicionar_capa", methods="POST")
     */
    public function adicionarCapa(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = $request->request->all();
        $file = $request->files->get('capa');

        $input = [
            'especie_uuid' => empty($data['especie_uuid']) ? null : $data['especie_uuid'],
            'capa' => empty($file) ? null : $file,
        ];

        if (empty($input['capa'])) {
            return new JsonResponse(['sem dados']);
        }

        $metadados = [
            'extensao' => empty($input['capa']->getClientOriginalExtension()) ? 'jpeg' : $input['capa']->getClientOriginalExtension(),
            'hash' => Uuid::v1(),
        ];

        $metadados['nome'] = $metadados['hash'] . '.' . $metadados['extensao'];

        try {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->getConnection()->beginTransaction();
            
            $especie = $entityManager->getRepository(Especie::class)->findOneBy(['uuid' => $input['especie_uuid']]);

            if ($especie->getCapa()) {
                return new JsonResponse(['já existe uma capa associada'], Response::HTTP_BAD_REQUEST);
            }

            $especie->setCapa($metadados['nome']);
            $especie->setMultimidiaCapa($input['capa']);

            $errors = $this->validatorManager->validate($especie);

            if ($this->validatorManager->hasError($errors)) {
                return $this->validatorManager->response();
            }    

            $entityManager->persist($especie);
            $entityManager->flush();

            $input['capa']->move($this->getParameter('public_directory_capas'), $metadados['nome']);

            $entityManager->getConnection()->commit();
        } catch (Exception $e) {
            $entityManager->getConnection()->rollBack();
        }

        return new JsonResponse($metadados);
    }

    /**
     * @Route("/alterar-dados-especie", name="alterar_dados_capa", methods="PUT")
     */
    public function alterarDadosCapa(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = $request->request->all();

        $input = [
            'especie_uuid' => empty($data['especie_uuid']) ? null : $data['especie_uuid'],
            'nome_cientifico' => empty($data['nome_cientifico']) ? null : $data['nome_cientifico'],
            'nome_popular' => empty($data['nome_popular']) ? null : $data['nome_popular'],
            'descricao' => empty($data['descricao']) ? null : $data['descricao'], 
        ];

        $entityManager = $this->getDoctrine()->getManager();

        $especie = $entityManager->getRepository(Especie::class)->findOneBy(['uuid' => $input['especie_uuid']]);

        $especie->setNomeCientifico($input['nome_cientifico']);
        $especie->setPrincipalNomePopular($input['nome_popular']);
        $especie->setDescricao($input['descricao']);

        $entityManager->persist($especie);
        $entityManager->flush();
        
        return new JsonResponse([
            'uuid' => $especie->getUuid(),
            'nome_cientifico' => $especie->getNomeCientifico(),
            'nome_popular' => $especie->getPrincipalNomePopular(),
            'descricao' => $especie->getDescricao(),
        ]);
    }

    /**
     * @Route("/alterar-dados-gerais", name="alterar_dados_gerais", methods="PUT")
     */
    public function AlterarDadosGerais(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = $request->request->all();

        $input = [
            'especie_uuid' => empty($data['especie_uuid']) ? null : $data['especie_uuid'],
            'nome_cientifico' => empty($data['nome_cientifico']) ? null : $data['nome_cientifico'],
            'nome_ingles' => empty($data['nome_ingles']) ? null : $data['nome_ingles'],
            'ano_descoberta' => empty($data['ano_descoberta']) ? null : $data['ano_descoberta'],
        ];

        $entityManager = $this->getDoctrine()->getManager();

        $especie = $entityManager->getRepository(Especie::class)->findOneBy(['uuid' => $input['especie_uuid']]);

        $especie->setNomeCientifico($input['nome_cientifico']);
        $especie->setNomeIngles($input['nome_ingles']);
        $especie->setAnoDescoberta($input['ano_descoberta']);

        $entityManager->persist($especie);
        $entityManager->flush();

        return new JsonResponse([
            'uuid' => $especie->getUuid(),
            'nome_cientifico' => $especie->getNomeCientifico(),
            'nome_ingles' => $especie->getNomeIngles(),
            'ano_descoberta' => $especie->getAnoDescoberta(),
        ]);
    }

    /**
     * @Route("/public/exibir-dados-gerais/{uuid}", name="exibir_dados_gerais", methods="GET")
     */
    public function exibirDadosGerais($uuid): Response
    {
        $especie = $this->getDoctrine()->getRepository(Especie::class)->findOneBy(['uuid' => $uuid]);
        $nomePopular = $this->getDoctrine()->getRepository(NomePopular::class);
        $descobridor = $this->getDoctrine()->getRepository(Descobridor::class);
        $marcador = $this->getDoctrine()->getRepository(Marcador::class);

        $output = [
            'nome_cientifico' => empty($especie->getNomeCientifico()) ? null : $especie->getNomeCientifico(),
            'nome_ingles' => empty($especie->getNomeIngles()) ? null : $especie->getNomeIngles(),
            'ano_descoberta' => empty($especie->getAnoDescoberta()) ? null : $especie->getAnoDescoberta(),
            'estado_conservacao' => empty($especie->getEstadoConservacao()) ? null : $especie->getEstadoConservacao()->getNome(),
            'qtd_nomes_populares' => $nomePopular->qtdNomesPopularesAssocidos($uuid),
            'qtd_descobridores' => $descobridor->qtdDescobridoresAssocidos($uuid),
            'qtd_marcadores' => $marcador->qtdMarcadoresAssocidos($uuid),
        ];
        
        return new JsonResponse([
            'nome_cientifico' => $output['nome_cientifico'],
            'nome_ingles' => $output['nome_ingles'],
            'ano_descoberta' => $output['ano_descoberta'],
            'estado_conservacao' => $output['estado_conservacao'],
            'qtd_nomes_populares' => $output['qtd_nomes_populares'],
            'qtd_descobridores' => $output['qtd_descobridores'],
            'qtd_marcadores' => $output['qtd_marcadores'],
        ]);
    }
}
