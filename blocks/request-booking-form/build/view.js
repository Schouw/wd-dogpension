/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./blocks/request-booking-form/src/BookingForm.jsx"
/*!*********************************************************!*\
  !*** ./blocks/request-booking-form/src/BookingForm.jsx ***!
  \*********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);



const emptyDog = () => ({
  id: Math.random().toString(36).substr(2, 9),
  name: '',
  breed: '',
  age: '',
  weight: '',
  notes: ''
});

/* =========================
   Felt-wrapper med info-ikon
   ========================= */
const FieldWithInfo = ({
  label,
  info,
  inputId,
  children,
  error,
  showError
}) => {
  const [open, setOpen] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
    className: "mhhc-form-group",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("label", {
      htmlFor: inputId,
      className: "mhhc-label",
      children: label
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
      className: "mhhc-input-row",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
        className: "mhhc-input-control",
        children: children
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("button", {
        type: "button",
        className: "mhhc-info-icon",
        "aria-expanded": open,
        "aria-controls": `${inputId}-help`,
        onClick: () => setOpen(o => !o),
        title: "Hvorfor er dette p\xE5kr\xE6vet?",
        tabIndex: -1,
        children: "\u2753"
      })]
    }), open && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
      id: `${inputId}-help`,
      className: "mhhc-help-text",
      role: "note",
      children: info
    }), showError && error && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
      className: "mhhc-field-error",
      children: error
    })]
  });
};
const BookingForm = () => {
  const [fromDate, setFromDate] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [toDate, setToDate] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [arrivalTime, setArrivalTime] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [departureTime, setDepartureTime] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [dogs, setDogs] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)([emptyDog()]);
  const [acceptTerms, setAcceptTerms] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [slots, setSlots] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [closedPeriods, setClosedPeriods] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [todayIso, setTodayIso] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const [maxDogs, setMaxDogs] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(2); // default fallback

  const [errors, setErrors] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({});
  const [submitted, setSubmitted] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [message, setMessage] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [priceEstimate, setPriceEstimate] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null);

  // Basispriser (kan overskrives af REST)
  const [priceDog1, setPriceDog1] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(250);
  const [priceDog2, setPriceDog2] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(200);
  const [specialPrices, setSpecialPrices] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)([]); // [{from,to,dog1,dog2}, ...]

  /* =========================
     Utils (datoer)
     ========================= */
  const dateFromISO = iso => {
    if (!iso) return null;
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(y, m - 1, d);
  };
  const toISO = dt => dt ? [dt.getFullYear(), String(dt.getMonth() + 1).padStart(2, '0'), String(dt.getDate()).padStart(2, '0')].join('-') : '';
  const addDays = (dt, days) => {
    const c = new Date(dt.getFullYear(), dt.getMonth(), dt.getDate());
    c.setDate(c.getDate() + days);
    return c;
  };
  const rangesOverlap = (aStart, aEnd, bStart, bEnd) => aStart <= bEnd && bStart <= aEnd;
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
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
        path: '/wddp_api/create_booking',
        method: 'POST',
        data: {
          from_date: fromDate,
          to_date: toDate,
          arrival_time: arrivalTime,
          departure_time: departureTime,
          price: priceEstimate,
          dogs
        }
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
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/wddp_api/config'
    }).then(res => {
      if (Array.isArray(res?.slots)) setSlots(res.slots);
      if (Array.isArray(res?.closedPeriods)) setClosedPeriods(res.closedPeriods);
      if (res?.today) setTodayIso(res.today);
      if (res?.maxDogs) setMaxDogs(res.maxDogs);

      // Hvis du udsender priser via REST, læs dem her
      if (res?.prices) {
        if (Number.isFinite(+res.prices.dog1)) setPriceDog1(Number(res.prices.dog1));
        if (Number.isFinite(+res.prices.dog2)) setPriceDog2(Number(res.prices.dog2));
        if (Array.isArray(res.prices.special)) setSpecialPrices(res.prices.special);
      }
    }).catch(err => {
      console.error('Fejl ved hentning af config:', err);
    });
  }, []);

  /* =========================
     Dato-validering (live)
     ========================= */
  const validateDates = (f = fromDate, t = toDate) => {
    const out = {};
    const today = todayIso ? dateFromISO(todayIso) : new Date();
    const earliest = addDays(new Date(today.getFullYear(), today.getMonth(), today.getDate()), 1);
    const fD = f ? dateFromISO(f) : null;
    const tD = t ? dateFromISO(t) : null;

    // Ikke i fortiden – hver for sig
    if (fD && fD < earliest) out.fromDate = `Datoer skal være tidligst ${toISO(earliest)} (i morgen).`;
    if (tD && tD < earliest) out.toDate = `Datoer skal være tidligst ${toISO(earliest)} (i morgen).`;

    // Fra ≤ Til – kun når begge er valgt
    if (fD && tD && fD > tD) {
      out.fromDate = 'Fra-dato skal være før eller samme dag som Til-dato.';
      out.toDate = 'Til-dato skal være efter eller samme dag som Fra-dato.';
    }

    // Lukket periode – kun når begge er valgt og ingen fejl ovenfor
    if (fD && tD && !out.fromDate && !out.toDate) {
      const overlap = closedPeriods.some(cp => {
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
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const dErr = validateDates();
    setErrors(prev => {
      const {
        fromDate,
        toDate,
        ...rest
      } = prev; // fjern gamle date-fejl
      return {
        ...rest,
        ...dErr
      }; // tilføj nye (hvis nogen)
    });
  }, [fromDate, toDate, todayIso, closedPeriods]);

  /* =========================
     Special-priser (normaliser)
     ========================= */
  const normSpecial = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
    const s = Array.isArray(specialPrices) ? specialPrices : [];
    return s.map(r => {
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
        dog2: Number.isFinite(d2) ? d2 : priceDog2
      };
    }).filter(Boolean);
  }, [specialPrices, priceDog1, priceDog2]);

  /* =========================
     Prisberegning (guards)
     ========================= */
  const hasBasePrices = Number.isFinite(priceDog1) && Number.isFinite(priceDog2);
  const datesAreValid = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
    if (!fromDate || !toDate) return false;
    const dErr = validateDates(fromDate, toDate);
    return !dErr.fromDate && !dErr.toDate;
  }, [fromDate, toDate, todayIso, JSON.stringify(closedPeriods)]);
  const calcPrice = react__WEBPACK_IMPORTED_MODULE_0___default().useCallback(async () => {
    // grund-guard
    if (!fromDate || !toDate) {
      setPriceEstimate(null);
      return;
    }
    try {
      const body = {
        fromDate,
        toDate,
        dogsCount: dogs.length
      };
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
        path: '/wddp_api/calculatePrice',
        method: 'POST',
        data: body
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
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    calcPrice();
  }, [calcPrice]);

  /* =========================
     UI handlers
     ========================= */
  const handleDogChange = (0,react__WEBPACK_IMPORTED_MODULE_0__.useCallback)((index, field, value) => {
    setDogs(prev => {
      const next = [...prev];
      next[index] = {
        ...next[index],
        [field]: value
      };
      return next;
    });
  }, []);
  const addDog = () => {
    console.log(maxDogs);
    if (dogs.length <= maxDogs) {
      setDogs(prev => [...prev, emptyDog()]);
    }
  };
  const removeDog = index => {
    if (dogs.length > 1) {
      const next = [...dogs];
      next.splice(index, 1);
      setDogs(next);
    }
  };

  // Fuldt tjek ved submit (inkl. hunde + checkbox)
  const isEmptyNumber = v => v === '' || v === null || Number.isNaN(Number(v));
  const handleSubmit = () => {
    setSubmitted(true);

    // 1) dato-fejl
    const dErr = validateDates(fromDate, toDate);

    // 2) required-tekster (datoer)
    const newErrors = {
      ...dErr
    };
    if (!fromDate && !newErrors.fromDate) newErrors.fromDate = 'Vælg en fra-dato.';
    if (!toDate && !newErrors.toDate) newErrors.toDate = 'Vælg en til-dato.';

    // 3) tider (krav)
    if (!arrivalTime) newErrors.arrivalTime = 'Vælg afleverings-tidspunkt.';
    if (!departureTime) newErrors.departureTime = 'Vælg afhentnings-tidspunkt.';
    if (arrivalTime && !slots.includes(arrivalTime)) newErrors.arrivalTime = 'Ugyldigt tidspunkt.';
    if (departureTime && !slots.includes(departureTime)) newErrors.departureTime = 'Ugyldigt tidspunkt.';

    // 4) Hund 1 (krævet)
    const h1 = dogs[0] || emptyDog();
    if (!h1.name.trim()) newErrors.dog_0_name = 'Navn er påkrævet.';
    if (!h1.breed.trim()) newErrors.dog_0_breed = 'Race er påkrævet.';
    if (!h1.age.trim()) newErrors.dog_0_age = 'Alder er påkrævet.';
    if (isEmptyNumber(h1.weight)) newErrors.dog_0_weight = 'Vægt er påkrævet.';

    // 5) Hund 2+ (kun hvis valgt)
    dogs.forEach((dog, i) => {
      if (!dog.name.trim()) newErrors[`dog_${i}_name`] = 'Navn er påkrævet.';
      if (!dog.breed.trim()) newErrors[`dog_${i}_breed`] = 'Race er påkrævet.';
      if (!dog.age || !dog.age.trim()) {
        newErrors[`dog_${i}_age`] = 'Alder er påkrævet.';
      }
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
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
    className: "mhhc-booking-form",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("h2", {
      children: "Book en plads i hundepensionen"
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
      className: "mhhc-form-grid",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(FieldWithInfo, {
        label: "Fra dato",
        info: "Vi skal kende perioden for at reservere plads og beregne prisen.",
        inputId: "fromDate",
        error: errors.fromDate,
        showError: showError('fromDate', true),
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("input", {
          id: "fromDate",
          type: "date",
          value: fromDate,
          onChange: e => setFromDate(e.target.value)
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(FieldWithInfo, {
        label: "Til dato",
        info: "Vi skal kende perioden for at reservere plads og beregne prisen.",
        inputId: "toDate",
        error: errors.toDate,
        showError: showError('toDate', true),
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("input", {
          id: "toDate",
          type: "date",
          value: toDate,
          onChange: e => setToDate(e.target.value)
        })
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
      className: "mhhc-form-grid",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(FieldWithInfo, {
        label: "Afleveringstidspunkt",
        info: "Hundepensionen har kun \xE5bent for aflevering i bestemte perioder for at skabe ro.",
        inputId: "arrivalTime",
        error: errors.arrivalTime,
        showError: showError('arrivalTime', true),
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("select", {
          id: "arrivalTime",
          value: arrivalTime,
          onChange: e => setArrivalTime(e.target.value),
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
            value: "",
            children: "V\xE6lg tidspunkt"
          }), slots.map((slot, index) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
            value: slot,
            children: slot
          }, index))]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(FieldWithInfo, {
        label: "Afhentningstidspunkt",
        info: "Hundepensionen har kun \xE5bent for afhentning i bestemte perioder for at skabe ro.",
        inputId: "departureTime",
        error: errors.departureTime,
        showError: showError('departureTime', true),
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("select", {
          id: "departureTime",
          value: departureTime,
          onChange: e => setDepartureTime(e.target.value),
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
            value: "",
            children: "V\xE6lg tidspunkt"
          }), slots.map((slot, index) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
            value: slot,
            children: slot
          }, index))]
        })
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("hr", {}), dogs.map((dog, index) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
      className: "mhhc-dog-fields",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("h3", {
        children: ["Hund ", index + 1]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
        className: "mhhc-form-grid",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(FieldWithInfo, {
          label: "Navn",
          info: "Vi vil gerne kende hundens navn.",
          inputId: `dog_${index}_name`,
          error: errors[`dog_${index}_name`],
          showError: showError(`dog_${index}_name`),
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("input", {
            id: `dog_${index}_name`,
            type: "text",
            value: dog.name,
            onChange: e => handleDogChange(index, 'name', e.target.value)
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(FieldWithInfo, {
          label: "Alder",
          info: "Alderen kan have betydning for pasning af hunden.",
          inputId: `dog_${index}_age`,
          error: errors[`dog_${index}_age`],
          showError: showError(`dog_${index}_age`),
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("input", {
            id: `dog_${index}_age`,
            text: true,
            value: dog.age,
            onChange: e => handleDogChange(index, 'age', e.target.value)
          })
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
        className: "mhhc-form-grid",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(FieldWithInfo, {
          label: "Race",
          info: "Race kan p\xE5virke hvor meget plads din hund har behov for.",
          inputId: `dog_${index}_breed`,
          error: errors[`dog_${index}_breed`],
          showError: showError(`dog_${index}_breed`),
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("input", {
            id: `dog_${index}_breed`,
            type: "text",
            value: dog.breed,
            onChange: e => handleDogChange(index, 'breed', e.target.value)
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(FieldWithInfo, {
          label: "V\xE6gt (kg)",
          info: "V\xE6gt kan p\xE5virke foder og h\xE5ndtering.",
          inputId: `dog_${index}_weight`,
          error: errors[`dog_${index}_weight`],
          showError: showError(`dog_${index}_weight`),
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("input", {
            id: `dog_${index}_weight`,
            type: "number",
            step: "0.1",
            min: "0",
            value: dog.weight,
            onChange: e => handleDogChange(index, 'weight', e.target.value)
          })
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
        className: "mhhc-form-group",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("label", {
          htmlFor: `dog_${index}_notes`,
          children: "Anm\xE6rkninger"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("textarea", {
          id: `dog_${index}_notes`,
          value: dog.notes,
          onChange: e => handleDogChange(index, 'notes', e.target.value)
        })]
      })]
    }, dog.id)), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
      className: "mhhc-form-buttons",
      children: [dogs.length < maxDogs && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("button", {
        type: "button",
        onClick: addDog,
        children: "\u2795 Tilf\xF8j hund"
      }), dogs.length > 1 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("button", {
        type: "button",
        onClick: () => removeDog(dogs.length - 1),
        children: "\u2796 Fjern sidste hund"
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("hr", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
      className: "mhhc-form-group",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("label", {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("input", {
          type: "checkbox",
          checked: acceptTerms,
          onChange: e => setAcceptTerms(e.target.checked)
        }), "Jeg har l\xE6st og accepteret betingelserne"]
      }), showError('acceptTerms') && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
        className: "mhhc-field-error",
        children: errors.acceptTerms
      })]
    }), message && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
      className: "mhhc-form-message",
      children: message
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
      className: "mhhc-price-box",
      children: priceEstimate !== null ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("strong", {
        children: ["Forventet pris: ", priceEstimate, " kr."]
      }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("span", {
        children: "Udfyld formularen for at se pris"
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
      className: "mhhc-submit-wrapper",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("button", {
        type: "button",
        onClick: handleSubmit,
        children: "Book nu"
      })
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (BookingForm);

/***/ },

/***/ "@wordpress/api-fetch"
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
(module) {

module.exports = window["wp"]["apiFetch"];

/***/ },

/***/ "@wordpress/element"
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
(module) {

module.exports = window["wp"]["element"];

/***/ },

/***/ "react"
/*!************************!*\
  !*** external "React" ***!
  \************************/
(module) {

module.exports = window["React"];

/***/ },

/***/ "react/jsx-runtime"
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
(module) {

module.exports = window["ReactJSXRuntime"];

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Check if module exists (development only)
/******/ 		if (__webpack_modules__[moduleId] === undefined) {
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*************************************************!*\
  !*** ./blocks/request-booking-form/src/view.js ***!
  \*************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _BookingForm_jsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./BookingForm.jsx */ "./blocks/request-booking-form/src/BookingForm.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);




document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('mhhc_hundepension_form');
  if (container) {
    const root = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createRoot)(container);
    root.render(/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_BookingForm_jsx__WEBPACK_IMPORTED_MODULE_2__["default"], {}));
  }
});
})();

/******/ })()
;
//# sourceMappingURL=view.js.map