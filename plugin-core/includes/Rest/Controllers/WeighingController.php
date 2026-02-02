<?php
/**
 * Weighing REST Controller.
 *
 * REST API controller for weight tracking.
 *
 * @package CanilCore
 */

namespace CanilCore\Rest\Controllers;

use CanilCore\Domain\Entities\Event;
use CanilCore\Domain\Exceptions\ValidationException;
use CanilCore\Domain\Services\WeighingService;
use CanilCore\Helpers\Sanitizer;
use CanilCore\Helpers\Validator;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WeighingController class.
 */
class WeighingController extends BaseController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'weighing';

	/**
	 * Required capability.
	 *
	 * @var string
	 */
	protected string $capability = 'manage_kennel';

	/**
	 * Weighing service.
	 *
	 * @var WeighingService
	 */
	private WeighingService $service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->service = new WeighingService();
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// POST /weighing - Record a weight measurement.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'record_weight' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_record_weight_args(),
			)
		);

		// POST /weighing/batch/{litter_id} - Batch record weights for a litter.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/batch/(?P<litter_id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'batch_record_weights' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_batch_record_args(),
			)
		);

		// GET /weighing/history/{entity_type}/{entity_id} - Get weight history.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/history/(?P<entity_type>[a-z]+)/(?P<entity_id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_weight_history' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_entity_args(),
			)
		);

		// GET /weighing/evolution/{entity_type}/{entity_id} - Get weight evolution for charts.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/evolution/(?P<entity_type>[a-z]+)/(?P<entity_id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_weight_evolution' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_evolution_args(),
			)
		);

		// GET /weighing/latest/{entity_type}/{entity_id} - Get latest weight.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/latest/(?P<entity_type>[a-z]+)/(?P<entity_id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_latest_weight' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_entity_args(),
			)
		);

		// GET /weighing/litter/{litter_id} - Get all puppy weights for a litter.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/litter/(?P<litter_id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_litter_weights' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_litter_weights_args(),
			)
		);

		// GET /weighing/gain/{entity_type}/{entity_id} - Get weight gain over period.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/gain/(?P<entity_type>[a-z]+)/(?P<entity_id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_weight_gain' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_weight_gain_args(),
			)
		);
	}

	/**
	 * Get record weight endpoint arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_record_weight_args(): array {
		return array(
			'entity_type' => array(
				'description' => __( 'Tipo de entidade (dog, puppy).', 'canil-core' ),
				'type'        => 'string',
				'required'    => true,
				'enum'        => array( Event::ENTITY_DOG, Event::ENTITY_PUPPY ),
			),
			'entity_id'   => array(
				'description' => __( 'ID da entidade.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'event_date'  => array(
				'description' => __( 'Data da pesagem.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
				'required'    => true,
			),
			'weight'      => array(
				'description' => __( 'Peso registrado.', 'canil-core' ),
				'type'        => 'number',
				'required'    => true,
				'minimum'     => 0,
			),
			'weight_unit' => array(
				'description' => __( 'Unidade de peso (g, kg, lb).', 'canil-core' ),
				'type'        => 'string',
				'default'     => 'kg',
				'enum'        => WeighingService::get_allowed_units(),
			),
			'type'        => array(
				'description' => __( 'Tipo de pesagem.', 'canil-core' ),
				'type'        => 'string',
				'default'     => 'general',
				'enum'        => WeighingService::get_allowed_weight_types(),
			),
			'notes'       => array(
				'description'       => __( 'Observações.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
	}

	/**
	 * Get batch record endpoint arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_batch_record_args(): array {
		return array(
			'litter_id'   => array(
				'description' => __( 'ID da ninhada.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'date'        => array(
				'description' => __( 'Data da pesagem.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
				'required'    => true,
			),
			'weights'     => array(
				'description' => __( 'Array de pesos dos filhotes.', 'canil-core' ),
				'type'        => 'array',
				'required'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'puppy_id' => array(
							'type'     => 'integer',
							'required' => true,
						),
						'weight'   => array(
							'type'     => 'number',
							'required' => true,
						),
						'unit'     => array(
							'type' => 'string',
							'enum' => WeighingService::get_allowed_units(),
						),
						'notes'    => array(
							'type' => 'string',
						),
					),
				),
			),
			'weight_unit' => array(
				'description' => __( 'Unidade de peso padrão (g, kg, lb).', 'canil-core' ),
				'type'        => 'string',
				'default'     => 'kg',
				'enum'        => WeighingService::get_allowed_units(),
			),
			'type'        => array(
				'description' => __( 'Tipo de pesagem.', 'canil-core' ),
				'type'        => 'string',
				'default'     => 'weekly',
				'enum'        => WeighingService::get_allowed_weight_types(),
			),
			'notes'       => array(
				'description'       => __( 'Observações gerais.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
	}

	/**
	 * Get entity endpoint arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_entity_args(): array {
		return array(
			'entity_type' => array(
				'description' => __( 'Tipo de entidade (dog, puppy).', 'canil-core' ),
				'type'        => 'string',
				'required'    => true,
				'enum'        => array( Event::ENTITY_DOG, Event::ENTITY_PUPPY ),
			),
			'entity_id'   => array(
				'description' => __( 'ID da entidade.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
		);
	}

	/**
	 * Get evolution endpoint arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_evolution_args(): array {
		$args = $this->get_entity_args();

		$args['unit'] = array(
			'description' => __( 'Unidade de peso para conversão (g, kg, lb).', 'canil-core' ),
			'type'        => 'string',
			'default'     => 'kg',
			'enum'        => WeighingService::get_allowed_units(),
		);

		return $args;
	}

	/**
	 * Get litter weights endpoint arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_litter_weights_args(): array {
		return array(
			'litter_id' => array(
				'description' => __( 'ID da ninhada.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'date'      => array(
				'description' => __( 'Filtrar por data específica.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
			),
		);
	}

	/**
	 * Get weight gain endpoint arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_weight_gain_args(): array {
		$args = $this->get_entity_args();

		$args['days'] = array(
			'description' => __( 'Número de dias para calcular o ganho.', 'canil-core' ),
			'type'        => 'integer',
			'default'     => 7,
			'minimum'     => 1,
			'maximum'     => 365,
		);

		return $args;
	}

	/**
	 * Record a weight measurement.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function record_weight( \WP_REST_Request $request ) {
		try {
			$data = $this->sanitize_record_input( $request );

			// Validate.
			$this->validate_record_weight( $data );

			// Record weight via service.
			$result = $this->service->record_weight(
				$data['entity_id'],
				$data['entity_type'],
				$data['event_date'],
				$data['weight'],
				$data['weight_unit'],
				$data['notes'],
				$data['type']
			);

			return new \WP_REST_Response(
				array(
					'data'    => $result['event'],
					'message' => $result['message'],
				),
				201
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Batch record weights for a litter.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 * @throws ValidationException If validation fails.
	 */
	public function batch_record_weights( \WP_REST_Request $request ) {
		try {
			$litter_id    = Sanitizer::int( $request->get_param( 'litter_id' ) );
			$date         = Sanitizer::date( $request->get_param( 'date' ) );
			$weights      = $request->get_param( 'weights' );
			$default_unit = Sanitizer::enum(
				$request->get_param( 'weight_unit' ),
				WeighingService::get_allowed_units(),
				WeighingService::UNIT_KILOGRAMS
			);
			$type         = Sanitizer::enum(
				$request->get_param( 'type' ),
				WeighingService::get_allowed_weight_types(),
				WeighingService::TYPE_WEEKLY
			);
			$notes        = Sanitizer::textarea( $request->get_param( 'notes' ) ?? '' );

			// Validate required fields.
			$validator = Validator::make(
				array(
					'litter_id' => $litter_id,
					'date'      => $date,
					'weights'   => $weights,
				)
			);
			$validator->required( 'litter_id', __( 'O ID da ninhada é obrigatório.', 'canil-core' ) )
				->positive_int( 'litter_id', __( 'O ID da ninhada deve ser um número positivo.', 'canil-core' ) )
				->required( 'date', __( 'A data é obrigatória.', 'canil-core' ) )
				->required( 'weights', __( 'Os pesos são obrigatórios.', 'canil-core' ) );

			if ( $validator->fails() ) {
				throw new ValidationException( $validator->get_errors() );
			}

			// Sanitize weights array.
			$sanitized_weights = $this->sanitize_batch_weights( $weights, $default_unit, $notes );

			// Batch record via service.
			$result = $this->service->batch_record_weights(
				$litter_id,
				$sanitized_weights,
				$date,
				$type
			);

			return new \WP_REST_Response(
				array(
					'data'    => $result['events'],
					'message' => $result['message'],
				),
				201
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get weight history for an entity.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_weight_history( \WP_REST_Request $request ) {
		try {
			$entity_type = Sanitizer::text( $request->get_param( 'entity_type' ) );
			$entity_id   = Sanitizer::int( $request->get_param( 'entity_id' ) );

			$history = $this->service->get_weight_history( $entity_id, $entity_type );

			return new \WP_REST_Response(
				array( 'data' => $history )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get weight evolution data for charts.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_weight_evolution( \WP_REST_Request $request ) {
		try {
			$entity_type = Sanitizer::text( $request->get_param( 'entity_type' ) );
			$entity_id   = Sanitizer::int( $request->get_param( 'entity_id' ) );
			$unit        = Sanitizer::enum(
				$request->get_param( 'unit' ),
				WeighingService::get_allowed_units(),
				WeighingService::UNIT_KILOGRAMS
			);

			$evolution = $this->service->get_weight_evolution( $entity_id, $entity_type, $unit );

			return new \WP_REST_Response(
				array( 'data' => $evolution )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get latest weight for an entity.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_latest_weight( \WP_REST_Request $request ) {
		try {
			$entity_type = Sanitizer::text( $request->get_param( 'entity_type' ) );
			$entity_id   = Sanitizer::int( $request->get_param( 'entity_id' ) );

			$latest = $this->service->get_latest_weight( $entity_id, $entity_type );

			return new \WP_REST_Response(
				array( 'data' => $latest )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get all puppy weights for a litter.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_litter_weights( \WP_REST_Request $request ) {
		try {
			$litter_id = Sanitizer::int( $request->get_param( 'litter_id' ) );
			$date      = Sanitizer::date( $request->get_param( 'date' ) );

			$weights = $this->service->get_litter_weights( $litter_id, $date );

			return new \WP_REST_Response(
				array( 'data' => $weights )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get weight gain over a period.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_weight_gain( \WP_REST_Request $request ) {
		try {
			$entity_type = Sanitizer::text( $request->get_param( 'entity_type' ) );
			$entity_id   = Sanitizer::int( $request->get_param( 'entity_id' ) );
			$days_param  = Sanitizer::int( $request->get_param( 'days' ) );
			$days        = $days_param > 0 ? $days_param : 7;

			// Limit days to reasonable range.
			$days = max( 1, min( $days, 365 ) );

			$gain = $this->service->calculate_weight_gain( $entity_id, $entity_type, $days );

			return new \WP_REST_Response(
				array( 'data' => $gain )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Sanitize record weight input.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array<string, mixed> Sanitized data.
	 */
	private function sanitize_record_input( \WP_REST_Request $request ): array {
		return array(
			'entity_type' => Sanitizer::enum(
				$request->get_param( 'entity_type' ),
				array( Event::ENTITY_DOG, Event::ENTITY_PUPPY )
			),
			'entity_id'   => Sanitizer::int( $request->get_param( 'entity_id' ) ),
			'event_date'  => Sanitizer::date( $request->get_param( 'event_date' ) ),
			'weight'      => Sanitizer::float( $request->get_param( 'weight' ) ),
			'weight_unit' => Sanitizer::enum(
				$request->get_param( 'weight_unit' ),
				WeighingService::get_allowed_units(),
				WeighingService::UNIT_KILOGRAMS
			),
			'type'        => Sanitizer::enum(
				$request->get_param( 'type' ),
				WeighingService::get_allowed_weight_types(),
				WeighingService::TYPE_GENERAL
			),
			'notes'       => Sanitizer::textarea( $request->get_param( 'notes' ) ?? '' ),
		);
	}

	/**
	 * Validate record weight data.
	 *
	 * @param array<string, mixed> $data Weight data.
	 * @throws ValidationException If validation fails.
	 */
	private function validate_record_weight( array $data ): void {
		$validator = Validator::make( $data )
			->required( 'entity_type', __( 'O tipo de entidade é obrigatório.', 'canil-core' ) )
			->in(
				'entity_type',
				array( Event::ENTITY_DOG, Event::ENTITY_PUPPY ),
				__( 'Tipo de entidade inválido.', 'canil-core' )
			)
			->required( 'entity_id', __( 'O ID da entidade é obrigatório.', 'canil-core' ) )
			->positive_int( 'entity_id', __( 'O ID da entidade deve ser um número positivo.', 'canil-core' ) )
			->required( 'event_date', __( 'A data é obrigatória.', 'canil-core' ) )
			->date( 'event_date', 'Y-m-d', __( 'Data inválida.', 'canil-core' ) )
			->required( 'weight', __( 'O peso é obrigatório.', 'canil-core' ) )
			->numeric( 'weight', __( 'O peso deve ser um número.', 'canil-core' ) );

		if ( $validator->fails() ) {
			throw new ValidationException( $validator->get_errors() );
		}

		// Additional validation for weight > 0.
		if ( (float) $data['weight'] <= 0 ) {
			throw new ValidationException(
				array( 'weight' => __( 'O peso deve ser maior que zero.', 'canil-core' ) )
			);
		}
	}

	/**
	 * Sanitize batch weights array.
	 *
	 * @param array  $weights      Raw weights array.
	 * @param string $default_unit Default weight unit.
	 * @param string $default_notes Default notes.
	 * @return array<array<string, mixed>> Sanitized weights.
	 */
	private function sanitize_batch_weights( array $weights, string $default_unit, string $default_notes ): array {
		$sanitized = array();

		foreach ( $weights as $weight_data ) {
			if ( ! is_array( $weight_data ) ) {
				continue;
			}

			$puppy_id = Sanitizer::int( $weight_data['puppy_id'] ?? 0 );
			$weight   = Sanitizer::float( $weight_data['weight'] ?? 0 );

			// Skip if no valid puppy_id or weight.
			if ( $puppy_id <= 0 || $weight <= 0 ) {
				continue;
			}

			$sanitized[] = array(
				'puppy_id' => $puppy_id,
				'weight'   => $weight,
				'unit'     => Sanitizer::enum(
					$weight_data['unit'] ?? $default_unit,
					WeighingService::get_allowed_units(),
					$default_unit
				),
				'notes'    => Sanitizer::textarea( $weight_data['notes'] ?? $default_notes ),
			);
		}

		return $sanitized;
	}
}
