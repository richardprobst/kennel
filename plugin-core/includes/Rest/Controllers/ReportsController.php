<?php
/**
 * Reports REST Controller.
 *
 * REST API controller for reports.
 *
 * @package CanilCore
 */

namespace CanilCore\Rest\Controllers;

use CanilCore\Domain\Services\ReportsService;
use CanilCore\Helpers\Sanitizer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ReportsController class.
 */
class ReportsController extends BaseController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'reports';

	/**
	 * Required capability.
	 *
	 * @var string
	 */
	protected string $capability = 'view_reports';

	/**
	 * Reports service.
	 *
	 * @var ReportsService
	 */
	private ReportsService $service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->service = new ReportsService();
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// GET /reports/plantel - Plantel (herd) report.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/plantel',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_plantel_report' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'status' => array(
						'description' => __( 'Filtrar por status.', 'canil-core' ),
						'type'        => 'string',
					),
					'sex'    => array(
						'description' => __( 'Filtrar por sexo.', 'canil-core' ),
						'type'        => 'string',
						'enum'        => array( 'male', 'female', '' ),
					),
					'format' => array(
						'description' => __( 'Formato de saída.', 'canil-core' ),
						'type'        => 'string',
						'default'     => 'json',
						'enum'        => array( 'json', 'csv' ),
					),
				),
			)
		);

		// GET /reports/litters - Litters report.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/litters',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_litters_report' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'start_date' => array(
						'description' => __( 'Data inicial (Y-m-d).', 'canil-core' ),
						'type'        => 'string',
						'format'      => 'date',
					),
					'end_date'   => array(
						'description' => __( 'Data final (Y-m-d).', 'canil-core' ),
						'type'        => 'string',
						'format'      => 'date',
					),
					'status'     => array(
						'description' => __( 'Filtrar por status.', 'canil-core' ),
						'type'        => 'string',
					),
					'format'     => array(
						'description' => __( 'Formato de saída.', 'canil-core' ),
						'type'        => 'string',
						'default'     => 'json',
						'enum'        => array( 'json', 'csv' ),
					),
				),
			)
		);

		// GET /reports/puppies - Puppies report.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/puppies',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_puppies_report' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'status' => array(
						'description' => __( 'Filtrar por status (available, reserved, sold, retained, deceased).', 'canil-core' ),
						'type'        => 'string',
					),
					'format' => array(
						'description' => __( 'Formato de saída.', 'canil-core' ),
						'type'        => 'string',
						'default'     => 'json',
						'enum'        => array( 'json', 'csv' ),
					),
				),
			)
		);

		// GET /reports/health - Health events report.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/health',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_health_report' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'start_date'  => array(
						'description' => __( 'Data inicial (Y-m-d).', 'canil-core' ),
						'type'        => 'string',
						'format'      => 'date',
					),
					'end_date'    => array(
						'description' => __( 'Data final (Y-m-d).', 'canil-core' ),
						'type'        => 'string',
						'format'      => 'date',
					),
					'event_type'  => array(
						'description' => __( 'Filtrar por tipo de evento.', 'canil-core' ),
						'type'        => 'string',
						'enum'        => array( 'vaccine', 'deworming', 'exam', 'medication', 'surgery', 'vet_visit', '' ),
					),
					'entity_type' => array(
						'description' => __( 'Filtrar por tipo de entidade.', 'canil-core' ),
						'type'        => 'string',
						'enum'        => array( 'dog', 'puppy', 'litter', '' ),
					),
					'format'      => array(
						'description' => __( 'Formato de saída.', 'canil-core' ),
						'type'        => 'string',
						'default'     => 'json',
						'enum'        => array( 'json', 'csv' ),
					),
				),
			)
		);
	}

	/**
	 * Get plantel (herd) report.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_plantel_report( \WP_REST_Request $request ) {
		try {
			$filters = array();

			if ( $request->get_param( 'status' ) ) {
				$filters['status'] = Sanitizer::text( $request->get_param( 'status' ) );
			}
			if ( $request->get_param( 'sex' ) ) {
				$filters['sex'] = Sanitizer::text( $request->get_param( 'sex' ) );
			}

			$format = Sanitizer::text( $request->get_param( 'format' ) ) ?: 'json';

			if ( 'csv' === $format ) {
				$csv = $this->service->get_plantel_csv( $filters );
				return $this->csv_response( $csv, 'plantel' );
			}

			$result = $this->service->get_plantel_report( $filters );

			return new \WP_REST_Response(
				array( 'data' => $result )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get litters report.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_litters_report( \WP_REST_Request $request ) {
		try {
			$start_date = Sanitizer::date( $request->get_param( 'start_date' ) );
			$end_date   = Sanitizer::date( $request->get_param( 'end_date' ) );
			$status     = Sanitizer::text( $request->get_param( 'status' ) ) ?: null;
			$format     = Sanitizer::text( $request->get_param( 'format' ) ) ?: 'json';

			if ( 'csv' === $format ) {
				$csv = $this->service->get_litters_csv( $start_date, $end_date, $status );
				return $this->csv_response( $csv, 'litters' );
			}

			$result = $this->service->get_litters_report( $start_date, $end_date, $status );

			return new \WP_REST_Response(
				array( 'data' => $result )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get puppies report.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_puppies_report( \WP_REST_Request $request ) {
		try {
			$status = Sanitizer::text( $request->get_param( 'status' ) ) ?: null;
			$format = Sanitizer::text( $request->get_param( 'format' ) ) ?: 'json';

			if ( 'csv' === $format ) {
				$csv = $this->service->get_puppies_csv( $status );
				return $this->csv_response( $csv, 'puppies' );
			}

			$result = $this->service->get_puppies_report( $status );

			return new \WP_REST_Response(
				array( 'data' => $result )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get health events report.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_health_report( \WP_REST_Request $request ) {
		try {
			$start_date  = Sanitizer::date( $request->get_param( 'start_date' ) );
			$end_date    = Sanitizer::date( $request->get_param( 'end_date' ) );
			$event_type  = Sanitizer::text( $request->get_param( 'event_type' ) ) ?: null;
			$entity_type = Sanitizer::text( $request->get_param( 'entity_type' ) ) ?: null;
			$format      = Sanitizer::text( $request->get_param( 'format' ) ) ?: 'json';

			if ( 'csv' === $format ) {
				$csv = $this->service->get_health_csv( $start_date, $end_date, $event_type, $entity_type );
				return $this->csv_response( $csv, 'health' );
			}

			$result = $this->service->get_health_report( $start_date, $end_date, $event_type, $entity_type );

			return new \WP_REST_Response(
				array( 'data' => $result )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Create CSV download response.
	 *
	 * @param string $csv      CSV content.
	 * @param string $filename Base filename.
	 * @return \WP_REST_Response Response object.
	 */
	private function csv_response( string $csv, string $filename ): \WP_REST_Response {
		$date     = gmdate( 'Y-m-d' );
		$filename = "relatorio-{$filename}-{$date}.csv";

		$response = new \WP_REST_Response( $csv );

		$response->set_headers(
			array(
				'Content-Type'        => 'text/csv; charset=utf-8',
				'Content-Disposition' => "attachment; filename=\"{$filename}\"",
				'Cache-Control'       => 'no-cache, no-store, must-revalidate',
				'Pragma'              => 'no-cache',
				'Expires'             => '0',
			)
		);

		return $response;
	}
}
