    import React, { useState, useEffect, useMemo, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';

const emptyDog = () => ({
    id: Math.random().toString(36).substr(2, 9),
    name: '',
    breed: '',
    age: '',
    weight: '',
    notes: '',
});

    /* =========================
       Felt-wrapper med info-ikon
       ========================= */
    const FieldWithInfo = ({ label, info, inputId, children, error, showError }) => {
        const [open, setOpen] = useState(false);

        return (
            <div className="mhhc-form-group">
                <label htmlFor={inputId} className="mhhc-label">
                    {label}
                </label>

                <div className="mhhc-input-row">
                    <div className="mhhc-input-control">{children}</div>
                    <button
                        type="button"
                        className="mhhc-info-icon"
                        aria-expanded={open}
                        aria-controls={`${inputId}-help`}
                        onClick={() => setOpen((o) => !o)}
                        title="Hvorfor er dette påkrævet?"
                        tabIndex={-1}
                    >❓</button>
                </div>

                {open && (
                    <div id={`${inputId}-help`} className="mhhc-help-text" role="note">
                        {info}
                    </div>
                )}

                {showError && error && <div className="mhhc-field-error">{error}</div>}
            </div>
        );
    };


const BookingForm = () => {
    const [fromDate, setFromDate] = useState('');
    const [toDate, setToDate] = useState('');
    const [arrivalTime, setArrivalTime] = useState('');
    const [departureTime, setDepartureTime] = useState('');
    const [dogs, setDogs] = useState([emptyDog()]);
    const [acceptTerms, setAcceptTerms] = useState(false);

    const [slots, setSlots] = useState([]);
    const [closedPeriods, setClosedPeriods] = useState([]);
    const [todayIso, setTodayIso] = useState(null);
    const [maxDogs, setMaxDogs] = useState(2); // default fallback

    const [errors, setErrors] = useState({});
    const [submitted, setSubmitted] = useState(false);
    const [message, setMessage] = useState('');
    const [priceEstimate, setPriceEstimate] = useState(null);

    // Basispriser (kan overskrives af REST)
    const [priceDog1, setPriceDog1] = useState(250);
    const [priceDog2, setPriceDog2] = useState(200);
    const [specialPrices, setSpecialPrices] = useState([]); // [{from,to,dog1,dog2}, ...]

    /* =========================
       Utils (datoer)
       ========================= */
    const dateFromISO = (iso) => {
        if (!iso) return null;
        const [y, m, d] = iso.split('-').map(Number);
        return new Date(y, m - 1, d);
    };
    const toISO = (dt) =>
        dt
            ? [
                dt.getFullYear(),
                String(dt.getMonth() + 1).padStart(2, '0'),
                String(dt.getDate()).padStart(2, '0'),
            ].join('-')
            : '';

    const addDays = (dt, days) => {
        const c = new Date(dt.getFullYear(), dt.getMonth(), dt.getDate());
        c.setDate(c.getDate() + days);
        return c;
    };

    const rangesOverlap = (aStart, aEnd, bStart, bEnd) =>
        aStart <= bEnd && bStart <= aEnd;

    const daysInclusive = (from, to) => {
        if (!from || !to) return 0;
        const f = dateFromISO(from),
            t = dateFromISO(to);
        if (!f || !t) return 0;
        const ms = (t - f) / (1000 * 60 * 60 * 24);
        return ms >= 0 ? Math.floor(ms) + 1 : 0; // inkl. samme dag
    };

    const submitBooking = async () => {
        try {
            const res = await apiFetch({
                path: '/wddp_api/create_booking',
                method: 'POST',
                data: {
                    from_date: fromDate,
                    to_date: toDate,
                    arrival_time: arrivalTime,
                    departure_time: departureTime,
                    price: priceEstimate,
                    dogs,
                },
            });

            if (res.redirect) {
                window.location.href = res.redirect;
            } else {
                setMessage('Fejl: Manglende redirect URL');
            }
        } catch (err) {
            console.error(err);
            setMessage('Der opstod en fejl ved oprettelse.');
        }
    };


    /* =========================
       Hent config fra REST
       ========================= */
    useEffect(() => {
        apiFetch({ path: '/wddp_api/config' })
            .then((res) => {
                if (Array.isArray(res?.slots)) setSlots(res.slots);
                if (Array.isArray(res?.closedPeriods)) setClosedPeriods(res.closedPeriods);
                if (res?.today) setTodayIso(res.today);
                if(res?.maxDogs) setMaxDogs(res.maxDogs);

                // Hvis du udsender priser via REST, læs dem her
                if (res?.prices) {
                    if (Number.isFinite(+res.prices.dog1)) setPriceDog1(Number(res.prices.dog1));
                    if (Number.isFinite(+res.prices.dog2)) setPriceDog2(Number(res.prices.dog2));
                    if (Array.isArray(res.prices.special)) setSpecialPrices(res.prices.special);
                }
            })
            .catch((err) => {
                console.error('Fejl ved hentning af config:', err);
            });
    }, []);

    /* =========================
       Dato-validering (live)
       ========================= */
    const validateDates = (f = fromDate, t = toDate) => {
        const out = {};
        const today = todayIso ? dateFromISO(todayIso) : new Date();
        const earliest = addDays(
            new Date(today.getFullYear(), today.getMonth(), today.getDate()),
            1
        );

        const fD = f ? dateFromISO(f) : null;
        const tD = t ? dateFromISO(t) : null;

        // Ikke i fortiden – hver for sig
        if (fD && fD < earliest)
            out.fromDate = `Datoer skal være tidligst ${toISO(earliest)} (i morgen).`;
        if (tD && tD < earliest)
            out.toDate = `Datoer skal være tidligst ${toISO(earliest)} (i morgen).`;

        // Fra ≤ Til – kun når begge er valgt
        if (fD && tD && fD > tD) {
            out.fromDate = 'Fra-dato skal være før eller samme dag som Til-dato.';
            out.toDate = 'Til-dato skal være efter eller samme dag som Fra-dato.';
        }

        // Lukket periode – kun når begge er valgt og ingen fejl ovenfor
        if (fD && tD && !out.fromDate && !out.toDate) {
            const overlap = closedPeriods.some((cp) => {
                const cFrom = dateFromISO(cp.from);
                const cTo = dateFromISO(cp.to);
                return cFrom && cTo && rangesOverlap(fD, tD, cFrom, cTo);
            });
            if (overlap) {
                out.fromDate = 'De valgte datoer overlapper en lukket periode.';
                out.toDate = 'De valgte datoer overlapper en lukket periode.';
            }
        }

        return out;
    };

    useEffect(() => {
        const dErr = validateDates();
        setErrors((prev) => {
            const { fromDate, toDate, ...rest } = prev; // fjern gamle date-fejl
            return { ...rest, ...dErr }; // tilføj nye (hvis nogen)
        });
    }, [fromDate, toDate, todayIso, closedPeriods]);

    /* =========================
       Special-priser (normaliser)
       ========================= */
    const normSpecial = useMemo(() => {
        const s = Array.isArray(specialPrices) ? specialPrices : [];
        return s
            .map((r) => {
                const f = dateFromISO(r.from);
                const t = dateFromISO(r.to);
                if (!f || !t) return null;
                const fromIso = toISO(f <= t ? f : t);
                const toIso = toISO(f <= t ? t : f);
                const d1 = Number(r.dog1);
                const d2 = Number(r.dog2);
                return {
                    from: fromIso,
                    to: toIso,
                    dog1: Number.isFinite(d1) ? d1 : priceDog1,
                    dog2: Number.isFinite(d2) ? d2 : priceDog2,
                };
            })
            .filter(Boolean);
    }, [specialPrices, priceDog1, priceDog2]);

    /* =========================
       Prisberegning (guards)
       ========================= */
    const hasBasePrices =
        Number.isFinite(priceDog1) && Number.isFinite(priceDog2);

    const datesAreValid = useMemo(() => {
        if (!fromDate || !toDate) return false;
        const dErr = validateDates(fromDate, toDate);
        return !dErr.fromDate && !dErr.toDate;
    }, [fromDate, toDate, todayIso, JSON.stringify(closedPeriods)]);

    const calcPrice = React.useCallback(async () => {
        // grund-guard
        if (!fromDate || !toDate) { setPriceEstimate(null); return; }

        try {
            const body = {
                fromDate,
                toDate,
                dogsCount: dogs.length
            };

            const res = await apiFetch({
                path: '/wddp_api/calculatePrice',
                method: 'POST',
                data: body,
            });

            // succes
            if (res && typeof res.total !== 'undefined') {
                setPriceEstimate(res.total);
                setMessage('');
            }
        } catch (e) {
            // serveren returnerer WP_Error med status 400 ved valideringsfejl
            setPriceEstimate(null);
            setMessage(e?.message || 'Kunne ikke beregne prisen.');
        }
    }, [fromDate, toDate, dogs.length]);

    useEffect(() => {
        calcPrice();
    }, [calcPrice]);

    /* =========================
       UI handlers
       ========================= */
    const handleDogChange = useCallback((index, field, value) => {
        setDogs((prev) => {
            const next = [...prev];
            next[index] = { ...next[index], [field]: value };
            return next;
        });
    }, []);

    const addDog = () => {
        console.log(maxDogs);
        if (dogs.length <= maxDogs) {
            setDogs(prev => [...prev, emptyDog()]);
        }
    };

    const removeDog = (index) => {
        if (dogs.length > 1) {
            const next = [...dogs];
            next.splice(index, 1);
            setDogs(next);
        }
    };


    // Fuldt tjek ved submit (inkl. hunde + checkbox)
    const isEmptyNumber = (v) =>
        v === '' || v === null || Number.isNaN(Number(v));

    const handleSubmit = () => {
        setSubmitted(true);

        // 1) dato-fejl
        const dErr = validateDates(fromDate, toDate);

        // 2) required-tekster (datoer)
        const newErrors = { ...dErr };
        if (!fromDate && !newErrors.fromDate)
            newErrors.fromDate = 'Vælg en fra-dato.';
        if (!toDate && !newErrors.toDate) newErrors.toDate = 'Vælg en til-dato.';

        // 3) tider (krav)
        if (!arrivalTime) newErrors.arrivalTime = 'Vælg afleverings-tidspunkt.';
        if (!departureTime)
            newErrors.departureTime = 'Vælg afhentnings-tidspunkt.';
        if (arrivalTime && !slots.includes(arrivalTime))
            newErrors.arrivalTime = 'Ugyldigt tidspunkt.';
        if (departureTime && !slots.includes(departureTime))
            newErrors.departureTime = 'Ugyldigt tidspunkt.';

        // 4) Hund 1 (krævet)
        const h1 = dogs[0] || emptyDog();
        if (!h1.name.trim()) newErrors.dog_0_name = 'Navn er påkrævet.';
        if (!h1.breed.trim()) newErrors.dog_0_breed = 'Race er påkrævet.';
        if (isEmptyNumber(h1.age)) newErrors.dog_0_age = 'Alder er påkrævet.';
        if (isEmptyNumber(h1.weight))
            newErrors.dog_0_weight = 'Vægt er påkrævet.';

        // 5) Hund 2+ (kun hvis valgt)
        dogs.forEach((dog, i) => {
            if (!dog.name.trim()) newErrors[`dog_${i}_name`] = 'Navn er påkrævet.';
            if (!dog.breed.trim()) newErrors[`dog_${i}_breed`] = 'Race er påkrævet.';
            if (isEmptyNumber(dog.age)) newErrors[`dog_${i}_age`] = 'Alder er påkrævet.';
            if (isEmptyNumber(dog.weight)) newErrors[`dog_${i}_weight`] = 'Vægt er påkrævet.';
        });

        // 6) Checkbox
        if (!acceptTerms) newErrors.acceptTerms = 'Du skal acceptere betingelserne.';

        // Gem fejl + besked
        setErrors(newErrors);
        const hasErrors = Object.values(newErrors).some(Boolean);

        // HVis fejl - så vis ret besked
        if (hasErrors) {
            setMessage('Ret venligst fejlene i formularen.');
            return;
        }

        // Hvis valid, så udfør booking
        setMessage('Formularen er klar til at blive sendt! ✅');
        submitBooking();



    };


    // Hjælper til at vise fejl-tekst (datoer = altid live; andre kun efter submit)
    const showError = (key, isDateField = false) => {
        if (!errors[key]) return false;
        return isDateField ? true : submitted;
    };

    return (
        <div className="mhhc-booking-form">
            <h2>Book en plads i hundepensionen</h2>

            <div className="mhhc-form-grid">
                <FieldWithInfo
                    label="Fra dato"
                    info="Vi skal kende perioden for at reservere plads og beregne prisen."
                    inputId="fromDate"
                    error={errors.fromDate}
                    showError={showError('fromDate', true)}
                >
                    <input
                        id="fromDate"
                        type="date"
                        value={fromDate}
                        onChange={(e) => setFromDate(e.target.value)}
                    />
                </FieldWithInfo>

                <FieldWithInfo
                    label="Til dato"
                    info="Vi skal kende perioden for at reservere plads og beregne prisen."
                    inputId="toDate"
                    error={errors.toDate}
                    showError={showError('toDate', true)}
                >
                    <input
                        id="toDate"
                        type="date"
                        value={toDate}
                        onChange={(e) => setToDate(e.target.value)}
                    />
                </FieldWithInfo>
            </div>

            <div className="mhhc-form-grid">
                <FieldWithInfo
                    label="Afleveringstidspunkt"
                    info="Hundepensionen har kun åbent for aflevering i bestemte perioder for at skabe ro."
                    inputId="arrivalTime"
                    error={errors.arrivalTime}
                    showError={showError('arrivalTime', true)}
                >
                    <select
                        id="arrivalTime"
                        value={arrivalTime}
                        onChange={(e) => setArrivalTime(e.target.value)}
                    >
                        <option value="">Vælg tidspunkt</option>
                        {slots.map((slot, index) => (
                            <option key={index} value={slot}>
                                {slot}
                            </option>
                        ))}
                    </select>
                </FieldWithInfo>

                <FieldWithInfo
                    label="Afhentningstidspunkt"
                    info="Hundepensionen har kun åbent for afhentning i bestemte perioder for at skabe ro."
                    inputId="departureTime"
                    error={errors.departureTime}
                    showError={showError('departureTime', true)}
                >
                    <select
                        id="departureTime"
                        value={departureTime}
                        onChange={(e) => setDepartureTime(e.target.value)}
                    >
                        <option value="">Vælg tidspunkt</option>
                        {slots.map((slot, index) => (
                            <option key={index} value={slot}>
                                {slot}
                            </option>
                        ))}
                    </select>
                </FieldWithInfo>
            </div>

            <hr />

            {/* Hunde */}
            {dogs.map((dog, index) => (
                <div key={dog.id} className="mhhc-dog-fields">
                    <h3>Hund {index + 1}</h3>


                    <div className="mhhc-form-grid">
                        <FieldWithInfo
                            label="Navn"
                            info="Vi vil gerne kende hundens navn."
                            inputId={`dog_${index}_name`}
                            error={errors[`dog_${index}_name`]}
                            showError={showError(`dog_${index}_name`)}
                        >
                            <input
                                id={`dog_${index}_name`}
                                type="text"
                                value={dog.name}
                                onChange={(e) => handleDogChange(index, 'name', e.target.value)}
                            />
                        </FieldWithInfo>

                        <FieldWithInfo
                            label="Alder"
                            info="Alderen kan have betydning for pasning af hunden."
                            inputId={`dog_${index}_age`}
                            error={errors[`dog_${index}_age`]}
                            showError={showError(`dog_${index}_age`)}
                        >
                            <input
                                id={`dog_${index}_age`}
                                type="number"
                                min="0"
                                value={dog.age}
                                onChange={(e) => handleDogChange(index, 'age', e.target.value)}
                            />
                        </FieldWithInfo>
                    </div>

                    <div className="mhhc-form-grid">
                        <FieldWithInfo
                            label="Race"
                            info="Race kan påvirke hvor meget plads din hund har behov for."
                            inputId={`dog_${index}_breed`}
                            error={errors[`dog_${index}_breed`]}
                            showError={showError(`dog_${index}_breed`)}
                        >
                            <input
                                id={`dog_${index}_breed`}
                                type="text"
                                value={dog.breed}
                                onChange={(e) => handleDogChange(index, 'breed', e.target.value)}
                            />
                        </FieldWithInfo>

                        <FieldWithInfo
                            label="Vægt (kg)"
                            info="Vægt kan påvirke foder og håndtering."
                            inputId={`dog_${index}_weight`}
                            error={errors[`dog_${index}_weight`]}
                            showError={showError(`dog_${index}_weight`)}
                        >
                            <input
                                id={`dog_${index}_weight`}
                                type="number"
                                step="0.1"
                                min="0"
                                value={dog.weight}
                                onChange={(e) =>
                                    handleDogChange(index, 'weight', e.target.value)
                                }
                            />
                        </FieldWithInfo>
                    </div>

                    <div className="mhhc-form-group">
                        <label htmlFor={`dog_${index}_notes`}>Anmærkninger</label>
                        <textarea
                            id={`dog_${index}_notes`}
                            value={dog.notes}
                            onChange={(e) => handleDogChange(index, 'notes', e.target.value)}
                        />
                    </div>
                </div>
            ))}

            {/* Tilføj/Fjern hunde */}
            <div className="mhhc-form-buttons">
                {dogs.length < maxDogs && (
                    <button type="button" onClick={addDog}>
                        ➕ Tilføj hund
                    </button>
                )}

                {dogs.length > 1 && (
                    <button type="button" onClick={() => removeDog(dogs.length - 1)}>
                        ➖ Fjern sidste hund
                    </button>
                )}
            </div>


            <hr />

            {/* Checkbox */}
            <div className="mhhc-form-group">
                <label>
                    <input
                        type="checkbox"
                        checked={acceptTerms}
                        onChange={(e) => setAcceptTerms(e.target.checked)}
                    />
                    Jeg har læst og accepteret betingelserne
                </label>
                {showError('acceptTerms') && (
                    <div className="mhhc-field-error">{errors.acceptTerms}</div>
                )}
            </div>

            {/* Infoboks */}
            {message && <div className="mhhc-form-message">{message}</div>}

            {/* Pris-boks */}
            <div className="mhhc-price-box">
                {priceEstimate !== null ? (
                    <strong>Forventet pris: {priceEstimate} kr.</strong>
                ) : (
                    <span>Udfyld formularen for at se pris</span>
                )}
            </div>

            <div className="mhhc-submit-wrapper">
                <button type="button" onClick={handleSubmit}>
                    Book nu
                </button>
            </div>
        </div>
    );
};

export default BookingForm;
