<?php

namespace Drupal\yudl_taxonomy_reconcile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\search_api\Entity\Index;

/**
 * Provides an OpenRefine reconciliation API for Drupal taxonomy terms.
 *
 * Exposes endpoints at /api/reconcile/{vocabulary} that conform to the
 * Reconciliation Service API specification, allowing OpenRefine and
 * compatible tools to match strings against taxonomy term authorities.
 */
class ReconcileController extends ControllerBase {

  /**
   * The Search API index used for taxonomy term queries.
   *
   * @var \Drupal\search_api\Entity\Index|null
   */
  protected $index;

  /**
   * The list of vocabulary machine names exposed by this endpoint.
   *
   * @var array<string>
   */
  protected array $allowedVocabularies = [
    'person',
    'subject',
    'corporate_body',
    'city',
    'city_section',
    'country',
    'county',
    'family',
    'genre',
    'geo_location',
    'language',
    'physical_form',
    'province',
    'region',
    'resource_types',
    'rights',
    'tags',
    'temporal_subjects',
  ];

  /**
   * Constructs a ReconcileController object.
   */
  public function __construct() {
    $this->index = Index::load('taxonomy_reconciliation');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Main endpoint handler for reconciliation requests.
   *
   * Handles both service description requests (GET with no queries parameter)
   * and reconciliation queries (GET or POST with a queries parameter containing
   * a JSON-encoded object of query objects).
   *
   * @param string $vocabulary
   *   The taxonomy vocabulary machine name to search within.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response conforming to the Reconciliation Service API spec.
   */
  public function reconcile(string $vocabulary, Request $request): JsonResponse {
    if (!in_array($vocabulary, $this->allowedVocabularies)) {
      return $this->corsResponse(new JsonResponse(['error' => 'Unknown vocabulary: ' . $vocabulary], 404));
    }

    // Handle JSONP callback if present (OpenRefine sometimes uses this).
    $callback = $request->get('callback');

    $rawQueries = $request->get('queries') ?? $request->request->get('queries');
    if (!$rawQueries) {
      $response = $this->serviceDescription($vocabulary);
      if ($callback) {
        return $this->jsonpResponse($callback, $response);
      }
      return $this->corsResponse($response);
    }

    $queries = json_decode($rawQueries, TRUE);
    if (!is_array($queries)) {
      return $this->corsResponse(new JsonResponse(['error' => 'Invalid queries parameter'], 400));
    }

    $results = [];
    foreach ($queries as $key => $queryData) {
      $results[$key] = [
        'result' => $this->search($vocabulary, $queryData),
      ];
    }

    $response = new JsonResponse($results);
    if ($callback) {
      return $this->jsonpResponse($callback, $response);
    }
    return $this->corsResponse($response);
  }

  /**
   * Wraps a JsonResponse with CORS headers required by OpenRefine.
   *
   * OpenRefine contacts reconciliation services directly from the browser,
   * so responses must include Access-Control headers to avoid being blocked
   * by same-origin policy.
   *
   * @param \Symfony\Component\HttpFoundation\JsonResponse $response
   *   The response to decorate.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The same response with CORS headers added.
   */
  private function corsResponse(JsonResponse $response): JsonResponse {
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
    return $response;
  }

  /**
   * Wraps a JsonResponse as a JSONP response for OpenRefine compatibility.
   *
   * OpenRefine may request responses wrapped in a JavaScript callback
   * function when operating in certain modes.
   *
   * @param string $callback
   *   The JavaScript callback function name.
   * @param \Symfony\Component\HttpFoundation\JsonResponse $response
   *   The JSON response to wrap.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A response with the JSON content wrapped in the callback function.
   */
  private function jsonpResponse(string $callback, JsonResponse $response): JsonResponse {
    $content = $response->getContent();
    $response->setContent($callback . '(' . $content . ')');
    $response->headers->set('Content-Type', 'application/javascript');
    $response->headers->set('Access-Control-Allow-Origin', '*');
    return $response;
  }

  /**
   * Builds the service description response for a given vocabulary.
   *
   * This is returned when the endpoint is called without a queries parameter,
   * which is how OpenRefine discovers the capabilities of the service.
   *
   * @param string $vocabulary
   *   The taxonomy vocabulary machine name.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the service metadata.
   */
  protected function serviceDescription(string $vocabulary): JsonResponse {
    $label = $this->vocabularyLabel($vocabulary);
    $baseUrl = \Drupal::request()->getSchemeAndHttpHost();

    return new JsonResponse([
      'name'            => 'Taxonomy Authority: ' . $label,
      'identifierSpace' => $baseUrl . '/taxonomy/term/',
      'schemaSpace'     => 'http://www.w3.org/2004/02/skos/core#',
      'defaultTypes'    => [
        ['id' => $vocabulary, 'name' => $label],
      ],
      'view' => [
        'url' => $baseUrl . '/taxonomy/term/{{id}}',
      ],
    ]);
  }

  /**
   * Root endpoint handler returning all vocabularies as available types.
   *
   * This is the main entry point for OpenRefine. It returns a service
   * description listing all available taxonomy vocabularies as types,
   * allowing the user to select which vocabulary to reconcile against
   * from within the OpenRefine dialog.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the service metadata and all available types.
   */
  public function root(Request $request): JsonResponse {
    $callback = $request->get('callback');
    $rawQueries = $request->get('queries') ?? $request->request->get('queries');

    // Service description — list all vocabularies as selectable types.
    if (!$rawQueries) {
      $baseUrl = \Drupal::request()->getSchemeAndHttpHost();
      $types = [];
      foreach ($this->allowedVocabularies as $vocabulary) {
        $types[] = [
          'id'   => $vocabulary,
          'name' => $this->vocabularyLabel($vocabulary),
        ];
      }

      $response = new JsonResponse([
        'name'            => 'YUDL Taxonomy Authorities',
        'identifierSpace' => $baseUrl . '/taxonomy/term/',
        'schemaSpace'     => 'http://www.w3.org/2004/02/skos/core#',
        'defaultTypes'    => $types,
        'view'            => [
          'url' => $baseUrl . '/taxonomy/term/{{id}}',
        ],
      ]);

      if ($callback) {
        return $this->jsonpResponse($callback, $response);
      }
      return $this->corsResponse($response);
    }

    // Route queries to the correct vocabulary based on the type parameter.
    $queries = json_decode($rawQueries, TRUE);
    if (!is_array($queries)) {
      return $this->corsResponse(new JsonResponse(['error' => 'Invalid queries parameter'], 400));
    }

    $results = [];
    foreach ($queries as $key => $queryData) {
      // OpenRefine sends the selected type as 'type' in the query object.
      $vocabulary = $queryData['type'] ?? NULL;

      if (!$vocabulary || !in_array($vocabulary, $this->allowedVocabularies)) {
        $results[$key] = ['result' => []];
        continue;
      }

      $results[$key] = [
        'result' => $this->search($vocabulary, $queryData),
      ];
    }

    $response = new JsonResponse($results);
    if ($callback) {
      return $this->jsonpResponse($callback, $response);
    }
    return $this->corsResponse($response);
  }

  /**
   * Executes a Search API query against the reconciliation index.
   *
   * Scores are normalized relative to the highest-scoring result in the
   * result set. Exact matches (case-insensitive) are always scored 100
   * and flagged for automatic matching in OpenRefine.
   *
   * @param string $vocabulary
   *   The taxonomy vocabulary machine name to filter results by.
   * @param array $queryData
   *   A reconciliation query array with at minimum a 'query' key containing
   *   the search string, and optionally a 'limit' key.
   *
   * @return array
   *   An array of reconciliation result objects, each containing id, name,
   *   score, match, and type keys.
   */
  protected function search(string $vocabulary, array $queryData): array {
    $term = trim($queryData['query'] ?? '');
    $limit = min((int) ($queryData['limit'] ?? 10), 50);

    if (empty($term) || !$this->index) {
      return [];
    }

    try {
      $query = $this->index->query();

      // Load parse mode via plugin manager instead of passing a string.
      $parseModeManager = \Drupal::service('plugin.manager.search_api.parse_mode');
      $parseMode = $parseModeManager->createInstance('terms');
      $query->setParseMode($parseMode);

      $query->keys($term);
      $query->setFulltextFields(['name']);
      $query->addCondition('vid', $vocabulary);
      $query->addCondition('status', 1);
      $query->range(0, $limit);

      $results  = $query->execute();
      $items    = $results->getResultItems();
      $raw      = [];
      $maxScore = 0;

      foreach ($items as $item) {
        $object = $item->getOriginalObject()?->getValue();
        if (!$object) {
          continue;
        }
        $score    = $item->getScore() ?? 0;
        $raw[]    = ['object' => $object, 'score' => $score];
        $maxScore = max($maxScore, $score);
      }

      $output = [];
      foreach ($raw as $r) {
        $name       = $r['object']->label();
        $normalized = $maxScore > 0 ? (int) round(($r['score'] / $maxScore) * 100) : 0;
        $exact      = strtolower($name) === strtolower($term);

        if ($exact) {
          $normalized = 100;
        }

        $output[] = [
          'id'    => (string) $r['object']->id(),
          'name'  => $name,
          'score' => $normalized,
          'match' => $exact,
          'type'  => [
            ['id' => $vocabulary, 'name' => $this->vocabularyLabel($vocabulary)],
          ],
        ];
      }

      usort($output, fn($a, $b) => $b['score'] <=> $a['score']);

      return $output;

    }
    catch (\Exception $e) {
      \Drupal::logger('taxonomy_reconcile')->error(
        'Reconciliation query failed for @vocab: @msg',
        ['@vocab' => $vocabulary, '@msg' => $e->getMessage()]
      );
      return [];
    }
  }

  /**
   * Returns a human-readable label for a vocabulary machine name.
   *
   * Loads the label directly from the taxonomy vocabulary config entity
   * rather than transforming the machine name, falling back to a
   * ucwords transformation if the vocabulary cannot be loaded.
   *
   * @param string $vocabulary
   *   The taxonomy vocabulary machine name.
   *
   * @return string
   *   The human-readable vocabulary label.
   */
  protected function vocabularyLabel(string $vocabulary): string {
    // Load the actual vocabulary label from config rather than guessing.
    $vocab = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_vocabulary')
      ->load($vocabulary);

    return $vocab ? $vocab->label() : ucwords(str_replace('_', ' ', $vocabulary));
  }

}
