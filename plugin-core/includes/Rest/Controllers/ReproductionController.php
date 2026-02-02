<?php
/**
 * Reproduction REST Controller.
 *
 * REST API controller for reproduction workflow.
 *
 * @package CanilCore
 */

namespace CanilCore\Rest\Controllers;

use CanilCore\Domain\Entities\Event;
use CanilCore\Domain\Entities\Litter;
use CanilCore\Domain\Services\ReproductionService;
use CanilCore\Helpers\Sanitizer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ReproductionController class.
 */
class ReproductionController extends BaseController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'reproduction';

	/**
	 * Required capability.
	 *
	 * @var string
	 */
	protected string $capability = 'manage_litters';

	/**
	 * Reproduction service.
	 *
	 * @var ReproductionService
	 */
	private ReproductionService $service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->service = new ReproductionService();
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// POST /reproduction/heat - Register heat start.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/heat',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'start_heat' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_heat_args(),
			)
		);

		// POST /reproduction/mating - Record mating.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/mating',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'record_mating' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_mating_args(),
			)
		);

		// POST /reproduction/pregnancy - Confirm pregnancy.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/pregnancy',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'confirm_pregnancy' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_pregnancy_args(),
			)
		);

		// POST /reproduction/birth - Record birth.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/birth',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'record_birth' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_birth_args(),
			)
		);

		// POST /reproduction/cancel/{litter_id} - Cancel litter.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/cancel/(?P<litter_id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'cancel_litter' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'litter_id' => array(
						'description' => __( 'ID da ninhada.', 'canil-core' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'reason'    => array(
						'description'       => __( 'Motivo do cancelamento.', 'canil-core' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);

		// GET /reproduction/timeline/{litter_id} - Get litter timeline.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/timeline/(?P<litter_id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_litter_timeline' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'litter_id' => array(
						'description' => __( 'ID da ninhada.', 'canil-core' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
			)
		);

		// GET /reproduction/history/{dog_id} - Get dog reproduction history.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/history/(?P<dog_id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_dog_history' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'dog_id' => array(
						'description' => __( 'ID do cão.', 'canil-core' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
			)
		);

		// GET /reproduction/upcoming-births - Get upcoming births.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/upcoming-births',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_upcoming_births' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'days' => array(
						'description' => __( 'Dias à frente para buscar.', 'canil-core' ),
						'type'        => 'integer',
						'default'     => 30,
					),
				),
			)
		);
	}

	/**
	 * Get heat arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_heat_args(): array {
		return array(
			'dam_id'    => array(
				'description' => __( 'ID da fêmea.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'heat_date' => array(
				'description' => __( 'Data do início do cio.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
				'required'    => true,
			),
			'notes'     => array(
				'description'       => __( 'Observações.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
	}

	/**
	 * Get mating arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_mating_args(): array {
		return array(
			'dam_id'          => array(
				'description' => __( 'ID da matriz (fêmea).', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'sire_id'         => array(
				'description' => __( 'ID do reprodutor (macho).', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'mating_date'     => array(
				'description' => __( 'Data da cobertura.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
				'required'    => true,
			),
			'mating_type'     => array(
				'description' => __( 'Tipo de cobertura.', 'canil-core' ),
				'type'        => 'string',
				'enum'        => Litter::get_allowed_mating_types(),
				'default'     => Litter::MATING_NATURAL,
			),
			'heat_start_date' => array(
				'description' => __( 'Data do início do cio.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
			),
			'notes'           => array(
				'description'       => __( 'Observações.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
	}

	/**
	 * Get pregnancy confirmation arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_pregnancy_args(): array {
		return array(
			'litter_id'         => array(
				'description' => __( 'ID da ninhada.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'confirmation_date' => array(
				'description' => __( 'Data da confirmação.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
				'required'    => true,
			),
			'method'            => array(
				'description' => __( 'Método de confirmação.', 'canil-core' ),
				'type'        => 'string',
				'enum'        => array( 'ultrasound', 'palpation', 'xray', 'blood_test', 'other' ),
				'default'     => 'ultrasound',
			),
			'notes'             => array(
				'description'       => __( 'Observações.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
	}

	/**
	 * Get birth arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_birth_args(): array {
		return array(
			'litter_id'  => array(
				'description' => __( 'ID da ninhada.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'birth_date' => array(
				'description' => __( 'Data do parto.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
				'required'    => true,
			),
			'birth_type' => array(
				'description' => __( 'Tipo de parto.', 'canil-core' ),
				'type'        => 'string',
				'enum'        => Litter::get_allowed_birth_types(),
				'default'     => Litter::BIRTH_NATURAL,
			),
			'notes'      => array(
				'description'       => __( 'Observações.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'puppies'    => array(
				'description' => __( 'Dados dos filhotes.', 'canil-core' ),
				'type'        => 'array',
				'default'     => array(),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'name'         => array( 'type' => 'string' ),
						'identifier'   => array( 'type' => 'string' ),
						'sex'          => array(
							'type' => 'string',
							'enum' => array( 'male', 'female' ),
						),
						'color'        => array( 'type' => 'string' ),
						'birth_weight' => array( 'type' => 'integer' ),
						'status'       => array( 'type' => 'string' ),
						'notes'        => array( 'type' => 'string' ),
					),
				),
			),
		);
	}

	/**
	 * Start heat.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function start_heat( \WP_REST_Request $request ) {
		try {
			$dam_id    = absint( $request->get_param( 'dam_id' ) );
			$heat_date = Sanitizer::date( $request->get_param( 'heat_date' ) );
			$notes     = Sanitizer::textarea( $request->get_param( 'notes' ) ) ?? '';

			$result = $this->service->start_heat( $dam_id, $heat_date, $notes );

			return new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => $result,
				),
				201
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Record mating.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function record_mating( \WP_REST_Request $request ) {
		try {
			$dam_id      = absint( $request->get_param( 'dam_id' ) );
			$sire_id     = absint( $request->get_param( 'sire_id' ) );
			$mating_date = Sanitizer::date( $request->get_param( 'mating_date' ) );

			$details = array(
				'mating_type'     => Sanitizer::text( $request->get_param( 'mating_type' ) ) ?? Litter::MATING_NATURAL,
				'heat_start_date' => Sanitizer::date( $request->get_param( 'heat_start_date' ) ),
				'notes'           => Sanitizer::textarea( $request->get_param( 'notes' ) ) ?? '',
			);

			$result = $this->service->record_mating( $dam_id, $sire_id, $mating_date, $details );

			return new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => $result,
				),
				201
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Confirm pregnancy.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function confirm_pregnancy( \WP_REST_Request $request ) {
		try {
			$litter_id         = absint( $request->get_param( 'litter_id' ) );
			$confirmation_date = Sanitizer::date( $request->get_param( 'confirmation_date' ) );
			$method            = Sanitizer::text( $request->get_param( 'method' ) ) ?? 'ultrasound';
			$notes             = Sanitizer::textarea( $request->get_param( 'notes' ) ) ?? '';

			$result = $this->service->confirm_pregnancy( $litter_id, $confirmation_date, $method, $notes );

			return new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => $result,
				),
				200
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Record birth.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function record_birth( \WP_REST_Request $request ) {
		try {
			$litter_id  = absint( $request->get_param( 'litter_id' ) );
			$birth_date = Sanitizer::date( $request->get_param( 'birth_date' ) );

			$birth_data = array(
				'birth_type' => Sanitizer::text( $request->get_param( 'birth_type' ) ) ?? Litter::BIRTH_NATURAL,
				'notes'      => Sanitizer::textarea( $request->get_param( 'notes' ) ) ?? '',
			);

			$puppies_data = $request->get_param( 'puppies' ) ?? array();

			$result = $this->service->record_birth( $litter_id, $birth_date, $birth_data, $puppies_data );

			return new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => $result,
				),
				201
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Cancel litter.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function cancel_litter( \WP_REST_Request $request ) {
		try {
			$litter_id = absint( $request->get_param( 'litter_id' ) );
			$reason    = Sanitizer::textarea( $request->get_param( 'reason' ) ) ?? '';

			$result = $this->service->cancel_litter( $litter_id, $reason );

			return new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => $result,
				),
				200
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get litter timeline.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_litter_timeline( \WP_REST_Request $request ) {
		try {
			$litter_id = absint( $request->get_param( 'litter_id' ) );

			$timeline = $this->service->get_litter_timeline( $litter_id );

			return new \WP_REST_Response(
				array(
					'data' => $timeline,
				)
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get dog reproduction history.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_dog_history( \WP_REST_Request $request ) {
		try {
			$dog_id = absint( $request->get_param( 'dog_id' ) );

			$history = $this->service->get_dog_reproduction_history( $dog_id );

			return new \WP_REST_Response(
				array(
					'data' => $history,
				)
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get upcoming births.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_upcoming_births( \WP_REST_Request $request ) {
		try {
			$days = absint( $request->get_param( 'days' ) ) ?: 30;

			$upcoming = $this->service->get_upcoming_births( $days );

			return new \WP_REST_Response(
				array(
					'data' => array_values( $upcoming ),
				)
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}
}
