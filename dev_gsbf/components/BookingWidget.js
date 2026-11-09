import {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import Calendar from './Calendar';
import SlotList from './SlotList';
import DetailsStep from './DetailsStep';
import DoneStep from './DoneStep';
import { isEmail } from '../utils/validate';
import {
	formatDateLong,
	formatDateShort,
	formatRange,
	monthOffset,
	monthWithOffset,
	partsInZone,
	todayInZone,
	addDays,
} from '../utils/time';

const EMPTY = { name: '', email: '', phone: '' };

/**
 * The booking form. Used by the block editor (mock api) and the front end
 * (REST api). Steps follow the design:
 *   layout "step":   pick (date + time) -> details -> done
 *   layout "single": date -> time -> details -> done
 * Email links add two entry modes: `view` (opens on the done screen) and
 * `reschedule` (current slot preselected, details read-only).
 */
export default function BookingWidget( { config, api, entry = null } ) {
	const tz = config.timezone;
	const single = config.layout === 'single';
	const today = useMemo( () => todayInZone( tz ), [ tz ] );
	const mountedAt = useRef( Date.now() );
	const honeypot = useRef( null );
	const root = useRef( null );

	const [ h24, setH24 ] = useState( false );
	const [ vm, setVm ] = useState( 0 );
	const [ monthCache, setMonthCache ] = useState( {} );
	const [ date, setDate ] = useState( null );
	const [ slots, setSlots ] = useState( null );
	const [ picked, setPicked ] = useState( null ); // { start, minutes }
	const [ step, setStep ] = useState( 'pick' ); // pick | details | done
	const [ sub, setSub ] = useState( 1 ); // single layout: 1 date, 2 time
	const [ fields, setFields ] = useState( EMPTY );
	const [ tried, setTried ] = useState( false );
	const [ saving, setSaving ] = useState( false );
	const [ submitError, setSubmitError ] = useState( '' );
	const [ pickError, setPickError ] = useState( '' );
	const [ result, setResult ] = useState( null );
	const [ kind, setKind ] = useState( 'new' ); // new | reschedule | view
	const [ existing, setExisting ] = useState( null );
	const [ entryState, setEntryState ] = useState(
		entry ? 'loading' : 'none'
	);
	const [ acknowledged, setAcknowledged ] = useState( false );

	const month = monthWithOffset( today, vm );
	const maxVm = Math.max(
		0,
		monthOffset( today, addDays( today, config.maxAdvance ) )
	);

	/* ---- email-link entry (view / reschedule) ------------------------- */
	useEffect( () => {
		if ( ! entry ) {
			return;
		}
		let cancelled = false;
		api.getBooking( entry.id, entry.token, entry.action )
			.then( ( booking ) => {
				if ( cancelled ) {
					return;
				}
				if ( booking.formId !== config.formId ) {
					// The link belongs to another form on this page.
					setEntryState( 'none' );
					return;
				}
				if ( entry.action === 'view' ) {
					setKind( 'view' );
					setResult( booking );
					setStep( 'done' );
				} else {
					const local = partsInZone(
						booking.start,
						booking.timezone
					);
					setKind( 'reschedule' );
					setExisting( booking );
					setFields( {
						name: booking.name,
						email: booking.email,
						phone: booking.phone,
					} );
					setDate( local.date );
					setVm( Math.max( 0, monthOffset( today, local.date ) ) );
					setPicked( {
						start: booking.start,
						minutes: local.minutes,
					} );
				}
				setEntryState( 'ready' );
				if ( root.current && root.current.scrollIntoView ) {
					root.current.scrollIntoView( { block: 'center' } );
				}
			} )
			.catch( () => ! cancelled && setEntryState( 'error' ) );
		return () => {
			cancelled = true;
		};
		// Runs once on mount.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	/* ---- month availability ------------------------------------------- */
	useEffect( () => {
		if ( monthCache[ month ] ) {
			return;
		}
		let cancelled = false;
		api.getMonth( month )
			.then( ( data ) => {
				if ( cancelled ) {
					return;
				}
				const map = new Map();
				( data.days || [] ).forEach( ( d ) =>
					map.set( d.date, d.available )
				);
				// A booking being rescheduled keeps its own day selectable.
				if ( existing ) {
					const own = partsInZone(
						existing.start,
						existing.timezone
					).date;
					if ( own.startsWith( month ) && map.has( own ) ) {
						map.set( own, true );
					}
				}
				setMonthCache( ( cache ) => ( { ...cache, [ month ]: map } ) );
			} )
			.catch( () => {} );
		return () => {
			cancelled = true;
		};
	}, [ month, api, monthCache, existing ] );

	/* ---- slots of the selected date ----------------------------------- */
	const loadSlots = useCallback(
		( day ) => {
			setSlots( null );
			return api
				.getDay( day )
				.then( ( data ) => {
					const list = ( data.slots || [] ).map( ( slot ) =>
						existing && slot.start === existing.start
							? { ...slot, available: true, current: true }
							: slot
					);
					setSlots( list );
				} )
				.catch( () => setSlots( [] ) );
		},
		[ api, existing ]
	);

	useEffect( () => {
		if ( date ) {
			loadSlots( date );
		}
	}, [ date, loadSlots ] );

	/* ---- derived ------------------------------------------------------- */
	const days = monthCache[ month ] || null;
	const ready = !! ( date && picked );
	const isSame = !! ( existing && picked && picked.start === existing.start );
	let stageOk = ready;
	if ( single ) {
		stageOk = sub === 1 ? !! date : !! picked;
	}
	const range = picked
		? formatRange( picked.minutes, config.duration, h24 )
		: '';
	const needsApproval = !! ( existing && existing.rescheduleNeedsApproval );

	const errors = {};
	if ( tried ) {
		if ( ! fields.name.trim() ) {
			errors.name = __(
				'Enter your name.',
				'gembooking-simple-booking-form'
			);
		}
		if ( ! isEmail( fields.email ) ) {
			errors.email = __(
				'Enter a valid email address.',
				'gembooking-simple-booking-form'
			);
		}
		if ( config.phoneRequired && ! fields.phone.trim() ) {
			errors.phone = __(
				'Enter your phone number.',
				'gembooking-simple-booking-form'
			);
		}
	}

	const reset = () => {
		setStep( 'pick' );
		setSub( 1 );
		setDate( null );
		setSlots( null );
		setPicked( null );
		setFields( EMPTY );
		setTried( false );
		setResult( null );
		setSubmitError( '' );
		setPickError( '' );
		setKind( 'new' );
		setExisting( null );
		setAcknowledged( false );
		setMonthCache( {} );
		mountedAt.current = Date.now();
	};

	/* ---- actions ------------------------------------------------------- */
	const pickDate = ( day ) => {
		setDate( day );
		setPicked( null );
		setPickError( '' );
	};

	const pickSlot = ( slot ) => {
		setPicked( { start: slot.start, minutes: slot.minutes } );
		setPickError( '' );
	};

	const next = () => {
		if ( ! stageOk ) {
			return;
		}
		setTried( false );
		if ( single && sub < 2 ) {
			setSub( sub + 1 );
		} else {
			setStep( 'details' );
		}
	};

	const back = () => {
		setSubmitError( '' );
		if ( step === 'details' ) {
			setStep( 'pick' );
			setSub( single ? 2 : 1 );
		} else if ( single && sub > 1 ) {
			setSub( sub - 1 );
		}
	};

	const submit = async () => {
		if ( saving ) {
			return;
		}
		setTried( true );
		setSubmitError( '' );

		if ( ! ready ) {
			setPickError(
				__(
					'Select a date and time.',
					'gembooking-simple-booking-form'
				)
			);
			return;
		}
		if ( kind === 'new' && Object.keys( errors ).length ) {
			return;
		}
		if (
			kind === 'new' &&
			( ! fields.name.trim() ||
				! isEmail( fields.email ) ||
				( config.phoneRequired && ! fields.phone.trim() ) )
		) {
			return;
		}
		if ( kind === 'reschedule' && isSame ) {
			setSubmitError(
				__(
					'Choose a different time to reschedule.',
					'gembooking-simple-booking-form'
				)
			);
			return;
		}

		setSaving( true );
		try {
			const booking =
				kind === 'reschedule'
					? await api.reschedule(
							entry.id,
							entry.token,
							picked.start
						)
					: await api.createBooking( {
							start: picked.start,
							name: fields.name.trim(),
							email: fields.email.trim(),
							phone: fields.phone.trim(),
							hp: honeypot.current ? honeypot.current.value : '',
							t: Date.now() - mountedAt.current,
						} );
			setResult( booking );
			setStep( 'done' );
		} catch ( e ) {
			if ( e.code === 'gsbf_slot_taken' ) {
				setPickError( e.message );
				setPicked( null );
				setStep( 'pick' );
				setSub( single ? 2 : 1 );
				loadSlots( date );
			} else {
				setSubmitError( e.message );
			}
		}
		setSaving( false );
	};

	/* ---- render -------------------------------------------------------- */
	const dayLabel = date ? formatDateShort( date ) : '';
	let footText = ready
		? `${ dayLabel } · ${ range }`
		: __( 'Select a date and time', 'gembooking-simple-booking-form' );
	if ( single && sub === 1 ) {
		footText = date
			? dayLabel
			: __( 'Select a date', 'gembooking-simple-booking-form' );
	} else if ( single ) {
		footText = picked ? `${ dayLabel } · ${ range }` : dayLabel;
	}

	let content;

	if ( entryState === 'loading' ) {
		content = (
			<div className="gsbf-status">
				{ __(
					'Loading your booking…',
					'gembooking-simple-booking-form'
				) }
			</div>
		);
	} else if ( step === 'done' && result ) {
		content = renderDone();
	} else if ( step === 'details' ) {
		content = (
			<DetailsStep
				summary={ `${ formatDateLong( date ) } · ${ range }` }
				fields={ fields }
				errors={ errors }
				phoneRequired={ config.phoneRequired }
				readOnly={ kind === 'reschedule' }
				warning={
					kind === 'reschedule' && needsApproval
						? __(
								'Your current time will be released right away. If the host does not approve the new time within 48 hours, your booking will be cancelled.',
								'gembooking-simple-booking-form'
							)
						: ''
				}
				acknowledged={ acknowledged }
				onAcknowledge={ setAcknowledged }
				pickError={ pickError }
				submitError={ submitError }
				saving={ saving }
				submitLabel={
					kind === 'reschedule'
						? __(
								'Confirm new time',
								'gembooking-simple-booking-form'
							)
						: __(
								'Confirm booking',
								'gembooking-simple-booking-form'
							)
				}
				honeypotRef={ honeypot }
				onChange={ ( key, value ) =>
					setFields( ( f ) => ( { ...f, [ key ]: value } ) )
				}
				onBack={ back }
				onSubmit={ submit }
			/>
		);
	} else {
		content = (
			<>
				{ entryState === 'error' && (
					<div className="gsbf-alert is-flush" role="alert">
						{ __(
							'This link is invalid or has expired. You can still book a new time below.',
							'gembooking-simple-booking-form'
						) }
					</div>
				) }
				{ kind === 'reschedule' && (
					<div className="gsbf-banner">
						{ __(
							'Choose a new date and time for your booking.',
							'gembooking-simple-booking-form'
						) }
					</div>
				) }
				{ pickError && (
					<div className="gsbf-alert is-flush" role="alert">
						{ pickError }
					</div>
				) }
				<div className="gsbf-pick">
					{ ( ! single || sub === 1 ) && (
						<Calendar
							month={ month }
							days={ days }
							selectedDate={ date }
							timezone={ tz }
							canPrev={ vm > 0 }
							canNext={ vm < maxVm }
							onPrev={ () => setVm( vm - 1 ) }
							onNext={ () => setVm( vm + 1 ) }
							onPick={ ( day ) => {
								pickDate( day );
							} }
						/>
					) }
					{ ( ! single || sub === 2 ) && (
						<SlotList
							title={
								dayLabel ||
								__(
									'Available times',
									'gembooking-simple-booking-form'
								)
							}
							date={ date }
							slots={ slots }
							selectedStart={ picked ? picked.start : null }
							h24={ h24 }
							onToggle24={ setH24 }
							onPick={ pickSlot }
							emptyText={
								config.days.length
									? __(
											'Select a date to see available times.',
											'gembooking-simple-booking-form'
										)
									: __(
											'No available days. Enable days in the block settings.',
											'gembooking-simple-booking-form'
										)
							}
						/>
					) }
				</div>
				<div className="gsbf-foot">
					<div className="gsbf-foot-text">{ footText }</div>
					<div className="gsbf-foot-actions">
						{ single && sub > 1 && (
							<button
								type="button"
								className="gsbf-btn-secondary"
								onClick={ back }
							>
								{ __(
									'Back',
									'gembooking-simple-booking-form'
								) }
							</button>
						) }
						<button
							type="button"
							className="gsbf-btn-primary"
							disabled={ ! stageOk }
							onClick={ next }
						>
							{ single
								? __( 'Next', 'gembooking-simple-booking-form' )
								: __(
										'Continue',
										'gembooking-simple-booking-form'
									) }
						</button>
					</div>
				</div>
			</>
		);
	}

	function renderDone() {
		const local = partsInZone( result.start, result.timezone );
		const pending = result.status === 'pending';
		const first = ( result.name || '' ).split( ' ' )[ 0 ];
		let title;
		let text;

		if ( kind === 'view' ) {
			title = pending
				? __(
						'Your booking is pending',
						'gembooking-simple-booking-form'
					)
				: __( 'Booking confirmed', 'gembooking-simple-booking-form' );
			text = pending
				? __(
						'This booking is waiting for the host to approve it. You will get a confirmation email once they do.',
						'gembooking-simple-booking-form'
					)
				: __(
						'Your booking is confirmed. We look forward to speaking with you.',
						'gembooking-simple-booking-form'
					);
		} else if ( kind === 'reschedule' ) {
			title = pending
				? __(
						'Your reschedule request is pending',
						'gembooking-simple-booking-form'
					)
				: __( 'Booking rescheduled', 'gembooking-simple-booking-form' );
			text = pending
				? sprintf(
						/* translators: %s: first name */
						__(
							'Thanks, %s. Your new time is waiting for the host to approve it. If it is not approved within 48 hours, your booking will be cancelled. We will email you.',
							'gembooking-simple-booking-form'
						),
						first || __( 'there', 'gembooking-simple-booking-form' )
					)
				: __(
						'Your booking is now at the new time. We emailed you the details.',
						'gembooking-simple-booking-form'
					);
		} else {
			title = pending
				? __(
						'Your booking is pending',
						'gembooking-simple-booking-form'
					)
				: __( 'Booking confirmed', 'gembooking-simple-booking-form' );
			text = pending
				? sprintf(
						/* translators: %s: first name */
						__(
							'Thanks, %s. Your booking is not confirmed yet. It is waiting for the host to approve it, and you will get a confirmation email once they do.',
							'gembooking-simple-booking-form'
						),
						first || __( 'there', 'gembooking-simple-booking-form' )
					)
				: config.message;
		}

		return (
			<DoneStep
				pending={ pending }
				title={ title }
				text={ text }
				rows={ [
					[
						__( 'Event', 'gembooking-simple-booking-form' ),
						result.title,
					],
					[
						__( 'Date', 'gembooking-simple-booking-form' ),
						formatDateLong( local.date ),
					],
					[
						__( 'Time', 'gembooking-simple-booking-form' ),
						formatRange( local.minutes, result.duration, h24 ),
					],
					[
						__( 'Timezone', 'gembooking-simple-booking-form' ),
						result.timezone,
					],
					[
						__( 'Name', 'gembooking-simple-booking-form' ),
						result.name,
					],
					[
						__( 'Email', 'gembooking-simple-booking-form' ),
						result.email,
					],
					[
						__( 'Booking ID', 'gembooking-simple-booking-form' ),
						result.ref,
						true,
					],
				] }
				onAnother={ reset }
			/>
		);
	}

	return (
		<div className="gsbf-frame" ref={ root }>
			{ content }
		</div>
	);
}
