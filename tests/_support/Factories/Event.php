<?php

namespace QL_Events\Test\Factories;

use Tribe__Events__Main as Main;

class Event extends \WP_UnitTest_Factory_For_Post {

	/**
	 * Returns a fluent event instance to start building an event meta information
	 * using readable syntax.
	 *
	 * @param string $start_date The event start date in string form.
	 *
	 * @return Fluent_Event The "head" of the fluent event.
	 */
	public function starting_on( string $start_date ) {
		$fluent_event = new Fluent_Event( $start_date );
		$fluent_event->set_factory($this);

		return $fluent_event;
	}

	/**
	 * Inserts an event in the database.
	 *
	 * @param array $args      An array of values to override the default arguments.
	 *                         Keep in mind `tax_input` and `meta_input` to bake terms and custom fields in.
	 *                         Notable arguments:
	 *                         `when` - by default events will happen in 24hrs; set this to a different hour offset
	 *                         to have them happen at a different time.
	 *                         `duration` - by defautl events will last for 2hrs; set this to a different duration
	 *                         in seconds if required.
	 *                         `utc_offset` - by default events will happen on UTC time; set this to a different hour
	 *                         offset if required.
	 *                         `venue` - set this to a venue post ID
	 *                         `organizers` - set this to an array of organizer post IDs
	 *
	 * @return int The generated event post ID
	 */
	function create_object( $args = array() ) {
		$args['post_type'] = $this->get_post_type();
		$args['post_status'] = isset( $args['post_status'] ) ? $args['post_status'] : 'publish';
		// by default an event will happen tomorrow
		$utc_start_time = isset( $args['when'] ) ? $args['when'] : '+24 hours';
		// by default an event will last 2hrs
		$duration = isset( $args['duration'] ) ? $args['duration'] : '7200';
		// by default an event will be on UTC time
		$utc_offset = isset( $args['utc_offset'] ) ? $args['utc_offset'] : 0;

		$start_timestamp = is_numeric( $utc_start_time ) ? $utc_start_time : strtotime( $utc_start_time );
		$end_timestamp   = $start_timestamp + $duration;

		$utc_start   = date( 'Y-m-d H:i:s', $start_timestamp );
		$local_start = date( 'Y-m-d H:i:s', $start_timestamp + $utc_offset * 3600 );
		$utc_end     = date( 'Y-m-d H:i:s', $end_timestamp );
		$local_end   = date( 'Y-m-d H:i:s', $end_timestamp + $utc_offset * 3600 );

		$meta_input = [
			'_EventStartDate'    => $local_start,
			'_EventEndDate'      => $local_end,
			'_EventStartDateUTC' => $utc_start,
			'_EventEndDateUTC'   => $utc_end,
			'_EventDuration'     => $duration,
		];

		if ( isset( $args['venue'] ) ) {
			$args['meta_input']['_EventVenueID'] = $args['venue'];
			unset( $args['venue'] );
		}

		if ( isset( $args['organizers']) || isset($args['organizer']) ) {
			$organizers = isset($args['organizers'])
				? (array)$args['organizers']
				: (array)$args['organizer'];
			unset( $args['organizers'] );
		}

		unset( $args['when'], $args['duration'], $args['utc_offset'] );

		$id = uniqid();
		$defaults = [
			'post_type'  => $this->get_post_type(),
			'post_title' => "Event {$id}",
			'post_name'  => "event-{$id}",
			'meta_input' => isset( $args['meta_input'] ) ? array_merge( $meta_input, $args['meta_input'] ) : $meta_input,
		];

		unset( $args['meta_input'] );

		$args = array_merge( $defaults, $args );

		$id = parent::create_object( $args );

		if ( ! empty( $organizers ) ) {
			foreach ( $organizers as $organizer ) {
				add_post_meta( $id, '_EventOrganizerID', $organizer );
			}
		}

		return $id;
	}

	/**
	 * Inserts many events in the database.
	 *
	 * @param      int $count The number of events to insert.
	 * @param array    $args  An array of arguments to override the defaults (see `haveEventInDatabase`),
	 *                        `time_space` - A positive amount of hours that should separate the events; by default the events
	 *                        will happen spaced one hour from each other.
	 * @param array    $generation_definitions
	 *
	 * @return array An array of generated event post IDs.
	 */
	function create_many( $count, $args = array(), $generation_definitions = null ) {
		$ids = [];
		$next_time = $time = empty( $args['time_space'] ) ? 1 : $args['time_space'];
		for ( $n = 0; $n < $count; $n ++ ) {
			$event_args = $args;
			if ( ! empty( $next_time ) ) {
				$event_args['when'] = '+' . $next_time . ' hours';
				$next_time += $time;
			}
			$ids[] = $this->create_object( $event_args );
		}

		return $ids;
	}

	/**
	 * @return string
	 */
	protected function get_post_type() {
		return Main::POSTTYPE;
	}
}
