<?php
/**
 * Health REST Controller.
 *
 * REST API controller for health management (vaccines, deworming, exams, medications, surgeries, vet visits).
 *
 * @package CanilCore
 */

namespace CanilCore\Rest\Controllers;

use CanilCore\Domain\Entities\Event;
use CanilCore\Domain\Exceptions\ValidationException;
use CanilCore\Domain\Services\HealthService;
use CanilCore\Helpers\Sanitizer;
use CanilCore\Helpers\Validator;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HealthController class.
 */
class HealthController extends BaseController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'health';

	/**
	 * Required capability.
	 *
	 * @var string
	 */
	protected string $capability = 'manage_kennel';

	/**
	 * Health service.
	 *
	 * @var HealthService
	 */
	private HealthService $service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->service = new HealthService();
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// POST /health/vaccine - Record a vaccine.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/vaccine',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'record_vaccine' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_vaccine_args(),
			)
		);

		// POST /health/deworming - Record deworming.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/deworming',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'record_deworming' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_deworming_args(),
			)
		);

		// POST /health/exam - Record an exam.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/exam',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'record_exam' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_exam_args(),
			)
		);

		// POST /health/medication - Record medication.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/medication',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'record_medication' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_medication_args(),
			)
		);

		// POST /health/surgery - Record surgery.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/surgery',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'record_surgery' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_surgery_args(),
			)
		);

		// POST /health/vet-visit - Record vet visit.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/vet-visit',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'record_vet_visit' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_vet_visit_args(),
			)
		);

		// GET /health/history/{entity_type}/{entity_id} - Get health history for an entity.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/history/(?P<entity_type>[a-z]+)/(?P<entity_id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_health_history' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'entity_type' => array(
						'description' => __( 'Tipo de entidade.', 'canil-core' ),
						'type'        => 'string',
						'required'    => true,
						'enum'        => Event::get_allowed_entity_types(),
					),
					'entity_id'   => array(
						'description' => __( 'ID da entidade.', 'canil-core' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
			)
		);

		// GET /health/upcoming-vaccines - Get upcoming vaccines.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/upcoming-vaccines',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_upcoming_vaccines' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'days' => array(
						'description' => __( 'Número de dias a frente.', 'canil-core' ),
						'type'        => 'integer',
						'default'     => 30,
						'minimum'     => 1,
						'maximum'     => 365,
					),
				),
			)
		);

		// GET /health/upcoming-dewormings - Get upcoming dewormings.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/upcoming-dewormings',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_upcoming_dewormings' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'days' => array(
						'description' => __( 'Número de dias a frente.', 'canil-core' ),
						'type'        => 'integer',
						'default'     => 30,
						'minimum'     => 1,
						'maximum'     => 365,
					),
				),
			)
		);

		// GET /health/overdue - Get overdue health events.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/overdue',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_overdue' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Get vaccine endpoint arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_vaccine_args(): array {
		return array(
			'entity_type'    => array(
				'description' => __( 'Tipo de entidade.', 'canil-core' ),
				'type'        => 'string',
				'required'    => true,
				'enum'        => Event::get_allowed_entity_types(),
			),
			'entity_id'      => array(
				'description' => __( 'ID da entidade.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'event_date'     => array(
				'description' => __( 'Data da vacina.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
				'required'    => true,
			),
			'name'           => array(
				'description'       => __( 'Nome da vacina.', 'canil-core' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'manufacturer'   => array(
				'description'       => __( 'Fabricante.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'batch'          => array(
				'description'       => __( 'Lote.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'next_dose_date' => array(
				'description' => __( 'Data da próxima dose.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
			),
			'notes'          => array(
				'description'       => __( 'Observações.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
	}

	/**
	 * Get deworming endpoint arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_deworming_args(): array {
		return array(
			'entity_type'    => array(
				'description' => __( 'Tipo de entidade.', 'canil-core' ),
				'type'        => 'string',
				'required'    => true,
				'enum'        => Event::get_allowed_entity_types(),
			),
			'entity_id'      => array(
				'description' => __( 'ID da entidade.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'event_date'     => array(
				'description' => __( 'Data da vermifugação.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
				'required'    => true,
			),
			'product'        => array(
				'description'       => __( 'Produto utilizado.', 'canil-core' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'dosage'         => array(
				'description'       => __( 'Dosagem.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'next_dose_date' => array(
				'description' => __( 'Data da próxima dose.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
			),
			'notes'          => array(
				'description'       => __( 'Observações.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
	}

	/**
	 * Get exam endpoint arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_exam_args(): array {
		return array(
			'entity_type'  => array(
				'description' => __( 'Tipo de entidade.', 'canil-core' ),
				'type'        => 'string',
				'required'    => true,
				'enum'        => Event::get_allowed_entity_types(),
			),
			'entity_id'    => array(
				'description' => __( 'ID da entidade.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'event_date'   => array(
				'description' => __( 'Data do exame.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
				'required'    => true,
			),
			'exam_type'    => array(
				'description'       => __( 'Tipo de exame.', 'canil-core' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'result'       => array(
				'description'       => __( 'Resultado do exame.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'attachments'  => array(
				'description' => __( 'Anexos (URLs).', 'canil-core' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
				),
			),
			'veterinarian' => array(
				'description'       => __( 'Veterinário.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'notes'        => array(
				'description'       => __( 'Observações.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
	}

	/**
	 * Get medication endpoint arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_medication_args(): array {
		return array(
			'entity_type' => array(
				'description' => __( 'Tipo de entidade.', 'canil-core' ),
				'type'        => 'string',
				'required'    => true,
				'enum'        => Event::get_allowed_entity_types(),
			),
			'entity_id'   => array(
				'description' => __( 'ID da entidade.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'event_date'  => array(
				'description' => __( 'Data de início da medicação.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
				'required'    => true,
			),
			'name'        => array(
				'description'       => __( 'Nome do medicamento.', 'canil-core' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'dosage'      => array(
				'description'       => __( 'Dosagem.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'frequency'   => array(
				'description'       => __( 'Frequência.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'end_date'    => array(
				'description' => __( 'Data de término.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
			),
			'notes'       => array(
				'description'       => __( 'Observações.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
	}

	/**
	 * Get surgery endpoint arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_surgery_args(): array {
		return array(
			'entity_type'  => array(
				'description' => __( 'Tipo de entidade.', 'canil-core' ),
				'type'        => 'string',
				'required'    => true,
				'enum'        => Event::get_allowed_entity_types(),
			),
			'entity_id'    => array(
				'description' => __( 'ID da entidade.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'event_date'   => array(
				'description' => __( 'Data da cirurgia.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
				'required'    => true,
			),
			'surgery_type' => array(
				'description'       => __( 'Tipo de cirurgia.', 'canil-core' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'veterinarian' => array(
				'description'       => __( 'Veterinário.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'notes'        => array(
				'description'       => __( 'Observações.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
	}

	/**
	 * Get vet visit endpoint arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_vet_visit_args(): array {
		return array(
			'entity_type'     => array(
				'description' => __( 'Tipo de entidade.', 'canil-core' ),
				'type'        => 'string',
				'required'    => true,
				'enum'        => Event::get_allowed_entity_types(),
			),
			'entity_id'       => array(
				'description' => __( 'ID da entidade.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'event_date'      => array(
				'description' => __( 'Data da consulta.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
				'required'    => true,
			),
			'reason'          => array(
				'description'       => __( 'Motivo da consulta.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'diagnosis'       => array(
				'description'       => __( 'Diagnóstico.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'treatment'       => array(
				'description'       => __( 'Tratamento.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'veterinarian'    => array(
				'description'       => __( 'Veterinário.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'next_visit_date' => array(
				'description' => __( 'Data da próxima consulta.', 'canil-core' ),
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
	 * Record a vaccine.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function record_vaccine( \WP_REST_Request $request ) {
		try {
			$data = $this->sanitize_vaccine_input( $request );

			// Validate input.
			$this->validate_vaccine_input( $data );

			/**
			 * Fires before a vaccine is recorded.
			 *
			 * @param array $data Sanitized vaccine data.
			 */
			do_action( 'canil_core_before_record_vaccine', $data );

			$result = $this->service->record_vaccine(
				$data['entity_id'],
				$data['entity_type'],
				$data['event_date'],
				array(
					'name'           => $data['name'],
					'manufacturer'   => $data['manufacturer'] ?? '',
					'batch'          => $data['batch'] ?? '',
					'next_dose_date' => $data['next_dose_date'] ?? null,
					'notes'          => $data['notes'] ?? '',
				)
			);

			/**
			 * Fires after a vaccine is recorded via REST API.
			 *
			 * @param array $result Result from HealthService.
			 * @param array $data   Original sanitized data.
			 */
			do_action( 'canil_core_after_record_vaccine', $result, $data );

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
	 * Record deworming.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function record_deworming( \WP_REST_Request $request ) {
		try {
			$data = $this->sanitize_deworming_input( $request );

			// Validate input.
			$this->validate_deworming_input( $data );

			/**
			 * Fires before a deworming is recorded.
			 *
			 * @param array $data Sanitized deworming data.
			 */
			do_action( 'canil_core_before_record_deworming', $data );

			$result = $this->service->record_deworming(
				$data['entity_id'],
				$data['entity_type'],
				$data['event_date'],
				array(
					'product'        => $data['product'],
					'dosage'         => $data['dosage'] ?? '',
					'next_dose_date' => $data['next_dose_date'] ?? null,
					'notes'          => $data['notes'] ?? '',
				)
			);

			/**
			 * Fires after a deworming is recorded via REST API.
			 *
			 * @param array $result Result from HealthService.
			 * @param array $data   Original sanitized data.
			 */
			do_action( 'canil_core_after_record_deworming', $result, $data );

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
	 * Record an exam.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function record_exam( \WP_REST_Request $request ) {
		try {
			$data = $this->sanitize_exam_input( $request );

			// Validate input.
			$this->validate_exam_input( $data );

			/**
			 * Fires before an exam is recorded.
			 *
			 * @param array $data Sanitized exam data.
			 */
			do_action( 'canil_core_before_record_exam', $data );

			$result = $this->service->record_exam(
				$data['entity_id'],
				$data['entity_type'],
				$data['event_date'],
				array(
					'type'         => $data['exam_type'],
					'result'       => $data['result'] ?? '',
					'attachments'  => $data['attachments'] ?? array(),
					'veterinarian' => $data['veterinarian'] ?? '',
					'notes'        => $data['notes'] ?? '',
				)
			);

			/**
			 * Fires after an exam is recorded via REST API.
			 *
			 * @param array $result Result from HealthService.
			 * @param array $data   Original sanitized data.
			 */
			do_action( 'canil_core_after_record_exam', $result, $data );

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
	 * Record medication.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function record_medication( \WP_REST_Request $request ) {
		try {
			$data = $this->sanitize_medication_input( $request );

			// Validate input.
			$this->validate_medication_input( $data );

			/**
			 * Fires before a medication is recorded.
			 *
			 * @param array $data Sanitized medication data.
			 */
			do_action( 'canil_core_before_record_medication', $data );

			$result = $this->service->record_medication(
				$data['entity_id'],
				$data['entity_type'],
				$data['event_date'],
				array(
					'name'      => $data['name'],
					'dosage'    => $data['dosage'] ?? '',
					'frequency' => $data['frequency'] ?? '',
					'end_date'  => $data['end_date'] ?? null,
					'notes'     => $data['notes'] ?? '',
				)
			);

			/**
			 * Fires after a medication is recorded via REST API.
			 *
			 * @param array $result Result from HealthService.
			 * @param array $data   Original sanitized data.
			 */
			do_action( 'canil_core_after_record_medication', $result, $data );

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
	 * Record surgery.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function record_surgery( \WP_REST_Request $request ) {
		try {
			$data = $this->sanitize_surgery_input( $request );

			// Validate input.
			$this->validate_surgery_input( $data );

			/**
			 * Fires before a surgery is recorded.
			 *
			 * @param array $data Sanitized surgery data.
			 */
			do_action( 'canil_core_before_record_surgery', $data );

			$result = $this->service->record_surgery(
				$data['entity_id'],
				$data['entity_type'],
				$data['event_date'],
				array(
					'type'         => $data['surgery_type'],
					'veterinarian' => $data['veterinarian'] ?? '',
					'notes'        => $data['notes'] ?? '',
				)
			);

			/**
			 * Fires after a surgery is recorded via REST API.
			 *
			 * @param array $result Result from HealthService.
			 * @param array $data   Original sanitized data.
			 */
			do_action( 'canil_core_after_record_surgery', $result, $data );

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
	 * Record vet visit.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function record_vet_visit( \WP_REST_Request $request ) {
		try {
			$data = $this->sanitize_vet_visit_input( $request );

			// Validate input.
			$this->validate_vet_visit_input( $data );

			/**
			 * Fires before a vet visit is recorded.
			 *
			 * @param array $data Sanitized vet visit data.
			 */
			do_action( 'canil_core_before_record_vet_visit', $data );

			$result = $this->service->record_vet_visit(
				$data['entity_id'],
				$data['entity_type'],
				$data['event_date'],
				array(
					'reason'          => $data['reason'] ?? '',
					'diagnosis'       => $data['diagnosis'] ?? '',
					'treatment'       => $data['treatment'] ?? '',
					'veterinarian'    => $data['veterinarian'] ?? '',
					'next_visit_date' => $data['next_visit_date'] ?? null,
					'notes'           => $data['notes'] ?? '',
				)
			);

			/**
			 * Fires after a vet visit is recorded via REST API.
			 *
			 * @param array $result Result from HealthService.
			 * @param array $data   Original sanitized data.
			 */
			do_action( 'canil_core_after_record_vet_visit', $result, $data );

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
	 * Get health history for an entity.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_health_history( \WP_REST_Request $request ) {
		try {
			$entity_type = Sanitizer::enum(
				$request->get_param( 'entity_type' ),
				Event::get_allowed_entity_types()
			);
			$entity_id   = Sanitizer::int( $request->get_param( 'entity_id' ) );

			if ( ! $entity_type ) {
				return new \WP_Error(
					'invalid_entity_type',
					__( 'Tipo de entidade inválido.', 'canil-core' ),
					array( 'status' => 400 )
				);
			}

			$history = $this->service->get_health_history( $entity_id, $entity_type );

			return new \WP_REST_Response(
				array(
					'data' => $history,
					'meta' => array(
						'entity_type' => $entity_type,
						'entity_id'   => $entity_id,
						'total'       => count( $history ),
					),
				)
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get upcoming vaccines.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_upcoming_vaccines( \WP_REST_Request $request ) {
		try {
			$days     = Sanitizer::int( $request->get_param( 'days' ) ) ?: 30;
			$days     = min( max( $days, 1 ), 365 );
			$vaccines = $this->service->get_upcoming_vaccines( $days );

			return new \WP_REST_Response(
				array(
					'data' => array_values( $vaccines ),
					'meta' => array(
						'days'  => $days,
						'total' => count( $vaccines ),
					),
				)
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get upcoming dewormings.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_upcoming_dewormings( \WP_REST_Request $request ) {
		try {
			$days       = Sanitizer::int( $request->get_param( 'days' ) ) ?: 30;
			$days       = min( max( $days, 1 ), 365 );
			$dewormings = $this->service->get_upcoming_dewormings( $days );

			return new \WP_REST_Response(
				array(
					'data' => array_values( $dewormings ),
					'meta' => array(
						'days'  => $days,
						'total' => count( $dewormings ),
					),
				)
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get overdue health events.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_overdue( \WP_REST_Request $request ) {
		try {
			$overdue = $this->service->get_overdue_health_events();

			return new \WP_REST_Response(
				array(
					'data' => array_values( $overdue ),
					'meta' => array(
						'total' => count( $overdue ),
					),
				)
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Sanitize vaccine input.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array<string, mixed> Sanitized data.
	 */
	private function sanitize_vaccine_input( \WP_REST_Request $request ): array {
		return array(
			'entity_type'    => Sanitizer::enum(
				$request->get_param( 'entity_type' ),
				Event::get_allowed_entity_types()
			),
			'entity_id'      => Sanitizer::int( $request->get_param( 'entity_id' ) ),
			'event_date'     => Sanitizer::date( $request->get_param( 'event_date' ) ),
			'name'           => Sanitizer::text( $request->get_param( 'name' ) ),
			'manufacturer'   => Sanitizer::text( $request->get_param( 'manufacturer' ) ?? '' ),
			'batch'          => Sanitizer::text( $request->get_param( 'batch' ) ?? '' ),
			'next_dose_date' => Sanitizer::date( $request->get_param( 'next_dose_date' ) ),
			'notes'          => Sanitizer::textarea( $request->get_param( 'notes' ) ?? '' ),
		);
	}

	/**
	 * Validate vaccine input.
	 *
	 * @param array<string, mixed> $data Sanitized data.
	 * @throws ValidationException If validation fails.
	 */
	private function validate_vaccine_input( array $data ): void {
		$validator = Validator::make( $data )
			->required( 'entity_type', __( 'O tipo de entidade é obrigatório.', 'canil-core' ) )
			->required( 'entity_id', __( 'O ID da entidade é obrigatório.', 'canil-core' ) )
			->required( 'event_date', __( 'A data da vacina é obrigatória.', 'canil-core' ) )
			->required( 'name', __( 'O nome da vacina é obrigatório.', 'canil-core' ) )
			->in( 'entity_type', Event::get_allowed_entity_types(), __( 'Tipo de entidade inválido.', 'canil-core' ) );

		if ( $validator->fails() ) {
			throw new ValidationException( $validator->get_errors() );
		}
	}

	/**
	 * Sanitize deworming input.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array<string, mixed> Sanitized data.
	 */
	private function sanitize_deworming_input( \WP_REST_Request $request ): array {
		return array(
			'entity_type'    => Sanitizer::enum(
				$request->get_param( 'entity_type' ),
				Event::get_allowed_entity_types()
			),
			'entity_id'      => Sanitizer::int( $request->get_param( 'entity_id' ) ),
			'event_date'     => Sanitizer::date( $request->get_param( 'event_date' ) ),
			'product'        => Sanitizer::text( $request->get_param( 'product' ) ),
			'dosage'         => Sanitizer::text( $request->get_param( 'dosage' ) ?? '' ),
			'next_dose_date' => Sanitizer::date( $request->get_param( 'next_dose_date' ) ),
			'notes'          => Sanitizer::textarea( $request->get_param( 'notes' ) ?? '' ),
		);
	}

	/**
	 * Validate deworming input.
	 *
	 * @param array<string, mixed> $data Sanitized data.
	 * @throws ValidationException If validation fails.
	 */
	private function validate_deworming_input( array $data ): void {
		$validator = Validator::make( $data )
			->required( 'entity_type', __( 'O tipo de entidade é obrigatório.', 'canil-core' ) )
			->required( 'entity_id', __( 'O ID da entidade é obrigatório.', 'canil-core' ) )
			->required( 'event_date', __( 'A data da vermifugação é obrigatória.', 'canil-core' ) )
			->required( 'product', __( 'O produto de vermifugação é obrigatório.', 'canil-core' ) )
			->in( 'entity_type', Event::get_allowed_entity_types(), __( 'Tipo de entidade inválido.', 'canil-core' ) );

		if ( $validator->fails() ) {
			throw new ValidationException( $validator->get_errors() );
		}
	}

	/**
	 * Sanitize exam input.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array<string, mixed> Sanitized data.
	 */
	private function sanitize_exam_input( \WP_REST_Request $request ): array {
		$attachments = $request->get_param( 'attachments' );
		if ( ! empty( $attachments ) && is_array( $attachments ) ) {
			$attachments = Sanitizer::array( $attachments, array( Sanitizer::class, 'url' ) );
		} else {
			$attachments = array();
		}

		return array(
			'entity_type'  => Sanitizer::enum(
				$request->get_param( 'entity_type' ),
				Event::get_allowed_entity_types()
			),
			'entity_id'    => Sanitizer::int( $request->get_param( 'entity_id' ) ),
			'event_date'   => Sanitizer::date( $request->get_param( 'event_date' ) ),
			'exam_type'    => Sanitizer::text( $request->get_param( 'exam_type' ) ),
			'result'       => Sanitizer::textarea( $request->get_param( 'result' ) ?? '' ),
			'attachments'  => $attachments,
			'veterinarian' => Sanitizer::text( $request->get_param( 'veterinarian' ) ?? '' ),
			'notes'        => Sanitizer::textarea( $request->get_param( 'notes' ) ?? '' ),
		);
	}

	/**
	 * Validate exam input.
	 *
	 * @param array<string, mixed> $data Sanitized data.
	 * @throws ValidationException If validation fails.
	 */
	private function validate_exam_input( array $data ): void {
		$validator = Validator::make( $data )
			->required( 'entity_type', __( 'O tipo de entidade é obrigatório.', 'canil-core' ) )
			->required( 'entity_id', __( 'O ID da entidade é obrigatório.', 'canil-core' ) )
			->required( 'event_date', __( 'A data do exame é obrigatória.', 'canil-core' ) )
			->required( 'exam_type', __( 'O tipo de exame é obrigatório.', 'canil-core' ) )
			->in( 'entity_type', Event::get_allowed_entity_types(), __( 'Tipo de entidade inválido.', 'canil-core' ) );

		if ( $validator->fails() ) {
			throw new ValidationException( $validator->get_errors() );
		}
	}

	/**
	 * Sanitize medication input.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array<string, mixed> Sanitized data.
	 */
	private function sanitize_medication_input( \WP_REST_Request $request ): array {
		return array(
			'entity_type' => Sanitizer::enum(
				$request->get_param( 'entity_type' ),
				Event::get_allowed_entity_types()
			),
			'entity_id'   => Sanitizer::int( $request->get_param( 'entity_id' ) ),
			'event_date'  => Sanitizer::date( $request->get_param( 'event_date' ) ),
			'name'        => Sanitizer::text( $request->get_param( 'name' ) ),
			'dosage'      => Sanitizer::text( $request->get_param( 'dosage' ) ?? '' ),
			'frequency'   => Sanitizer::text( $request->get_param( 'frequency' ) ?? '' ),
			'end_date'    => Sanitizer::date( $request->get_param( 'end_date' ) ),
			'notes'       => Sanitizer::textarea( $request->get_param( 'notes' ) ?? '' ),
		);
	}

	/**
	 * Validate medication input.
	 *
	 * @param array<string, mixed> $data Sanitized data.
	 * @throws ValidationException If validation fails.
	 */
	private function validate_medication_input( array $data ): void {
		$validator = Validator::make( $data )
			->required( 'entity_type', __( 'O tipo de entidade é obrigatório.', 'canil-core' ) )
			->required( 'entity_id', __( 'O ID da entidade é obrigatório.', 'canil-core' ) )
			->required( 'event_date', __( 'A data de início da medicação é obrigatória.', 'canil-core' ) )
			->required( 'name', __( 'O nome do medicamento é obrigatório.', 'canil-core' ) )
			->in( 'entity_type', Event::get_allowed_entity_types(), __( 'Tipo de entidade inválido.', 'canil-core' ) );

		if ( $validator->fails() ) {
			throw new ValidationException( $validator->get_errors() );
		}
	}

	/**
	 * Sanitize surgery input.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array<string, mixed> Sanitized data.
	 */
	private function sanitize_surgery_input( \WP_REST_Request $request ): array {
		return array(
			'entity_type'  => Sanitizer::enum(
				$request->get_param( 'entity_type' ),
				Event::get_allowed_entity_types()
			),
			'entity_id'    => Sanitizer::int( $request->get_param( 'entity_id' ) ),
			'event_date'   => Sanitizer::date( $request->get_param( 'event_date' ) ),
			'surgery_type' => Sanitizer::text( $request->get_param( 'surgery_type' ) ),
			'veterinarian' => Sanitizer::text( $request->get_param( 'veterinarian' ) ?? '' ),
			'notes'        => Sanitizer::textarea( $request->get_param( 'notes' ) ?? '' ),
		);
	}

	/**
	 * Validate surgery input.
	 *
	 * @param array<string, mixed> $data Sanitized data.
	 * @throws ValidationException If validation fails.
	 */
	private function validate_surgery_input( array $data ): void {
		$validator = Validator::make( $data )
			->required( 'entity_type', __( 'O tipo de entidade é obrigatório.', 'canil-core' ) )
			->required( 'entity_id', __( 'O ID da entidade é obrigatório.', 'canil-core' ) )
			->required( 'event_date', __( 'A data da cirurgia é obrigatória.', 'canil-core' ) )
			->required( 'surgery_type', __( 'O tipo de cirurgia é obrigatório.', 'canil-core' ) )
			->in( 'entity_type', Event::get_allowed_entity_types(), __( 'Tipo de entidade inválido.', 'canil-core' ) );

		if ( $validator->fails() ) {
			throw new ValidationException( $validator->get_errors() );
		}
	}

	/**
	 * Sanitize vet visit input.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array<string, mixed> Sanitized data.
	 */
	private function sanitize_vet_visit_input( \WP_REST_Request $request ): array {
		return array(
			'entity_type'     => Sanitizer::enum(
				$request->get_param( 'entity_type' ),
				Event::get_allowed_entity_types()
			),
			'entity_id'       => Sanitizer::int( $request->get_param( 'entity_id' ) ),
			'event_date'      => Sanitizer::date( $request->get_param( 'event_date' ) ),
			'reason'          => Sanitizer::text( $request->get_param( 'reason' ) ?? '' ),
			'diagnosis'       => Sanitizer::textarea( $request->get_param( 'diagnosis' ) ?? '' ),
			'treatment'       => Sanitizer::textarea( $request->get_param( 'treatment' ) ?? '' ),
			'veterinarian'    => Sanitizer::text( $request->get_param( 'veterinarian' ) ?? '' ),
			'next_visit_date' => Sanitizer::date( $request->get_param( 'next_visit_date' ) ),
			'notes'           => Sanitizer::textarea( $request->get_param( 'notes' ) ?? '' ),
		);
	}

	/**
	 * Validate vet visit input.
	 *
	 * @param array<string, mixed> $data Sanitized data.
	 * @throws ValidationException If validation fails.
	 */
	private function validate_vet_visit_input( array $data ): void {
		$validator = Validator::make( $data )
			->required( 'entity_type', __( 'O tipo de entidade é obrigatório.', 'canil-core' ) )
			->required( 'entity_id', __( 'O ID da entidade é obrigatório.', 'canil-core' ) )
			->required( 'event_date', __( 'A data da consulta é obrigatória.', 'canil-core' ) )
			->in( 'entity_type', Event::get_allowed_entity_types(), __( 'Tipo de entidade inválido.', 'canil-core' ) );

		if ( $validator->fails() ) {
			throw new ValidationException( $validator->get_errors() );
		}
	}
}
