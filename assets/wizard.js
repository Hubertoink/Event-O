/**
 * Event-O Wizard – Guided step-by-step event creation/editing.
 *
 * Reads existing form values (on edit) and writes back into the real metabox
 * fields + title + content so the normal save_post handler persists everything.
 */
(function ($) {
    'use strict';

    /* ── bootstrapped data from wp_localize_script ── */
    var D = window.eoWizardData || {};
    var categories = D.categories || [];
    var venues     = D.venues || [];
    var organizers = D.organizers || [];
    var tags       = D.tags || [];
    var isNew      = D.isNew === '1';
    var canPublish = D.canPublish === '1';
    var texts      = D.texts || {};

    /* ── Gutenberg detection ── */
    var isGutenberg = !!(typeof wp !== 'undefined' && wp.data && wp.data.select && wp.data.select('core/editor'));

    /* ── state ── */
    var currentStep = 0;
    var totalSteps  = 5;
    var $overlay, $modal;
    var wizardState = {};
    var featuredImageId  = 0;
    var featuredImageUrl = '';
    var galleryImages    = []; // [{id, url}]

    /* ================================================================
       INIT
       ================================================================ */
    $(function () {
        // In Gutenberg the metabox may render late; wait a bit for DOM
        var launchDelay = isGutenberg ? 1500 : 0;
        setTimeout(function () { injectLaunchButton(); }, launchDelay);
        if (isNew) {
            openWizard();
        }
    });

    /* ================================================================
       LAUNCH BUTTON (shown above metabox on existing posts)
       ================================================================ */
    function injectLaunchButton() {
        var $wrap = $('#event_o_event_details');
        if (!$wrap.length) return;
        var $btn = $('<div class="eo-wizard-launch"><button type="button" class="eo-wizard-launch-btn"><span class="dashicons dashicons-welcome-learn-more"></span> ' + esc(texts.openWizard || 'Event-Wizard öffnen') + '</button></div>');
        $wrap.before($btn);
        $btn.on('click', function () { openWizard(); });
    }

    /* ================================================================
       BUILD OVERLAY
       ================================================================ */
    function openWizard() {
        if ($overlay) { $overlay.addClass('is-visible'); return; }

        readExistingValues();
        currentStep = 0;

        $overlay = $('<div class="eo-wizard-overlay">');
        $modal   = $('<div class="eo-wizard-modal">');
        $overlay.append($modal);
        $('body').append($overlay);

        renderStep();
        requestAnimationFrame(function () { $overlay.addClass('is-visible'); });

        // ESC key
        $(document).on('keydown.eoWizard', function (e) {
            if (e.key === 'Escape') closeWizard();
        });
    }

    function closeWizard() {
        if (!$overlay) return;
        $overlay.removeClass('is-visible');
        setTimeout(function () { $overlay.remove(); $overlay = null; $modal = null; }, 300);
        $(document).off('keydown.eoWizard');
    }

    /* ================================================================
       READ EXISTING FORM VALUES INTO wizardState
       ================================================================ */
    function readExistingValues() {
        wizardState = {
            title:       getTitle(),
            description: getEditorContent(),
            categories:  getTaxIds('event_o_category'),
            tags:        getTagIds(),
            startDate1:  $('#event_o_start_datetime').val() || '',
            endDate1:    $('#event_o_end_datetime').val() || '',
            beginTime1:  $('#event_o_begin_time').val() || '',
            startDate2:  $('#event_o_start_datetime_2').val() || '',
            endDate2:    $('#event_o_end_datetime_2').val() || '',
            beginTime2:  $('#event_o_begin_time_2').val() || '',
            startDate3:  $('#event_o_start_datetime_3').val() || '',
            endDate3:    $('#event_o_end_datetime_3').val() || '',
            beginTime3:  $('#event_o_begin_time_3').val() || '',
            venues:      getTaxIds('event_o_venue'),
            organizers:  getTaxIds('event_o_organizer'),
            price:       $('#event_o_price').val() || '',
            status:      $('#event_o_status').val() || '',
            statusLabel: $('#event_o_status_label').val() || '',
            isHighlight: $('#event_o_highlight').is(':checked'),
            highlightUntil: $('#event_o_highlight_until').val() || '',
            bands:       parseBands($('#event_o_bands').val() || ''),
            galleryIds:  ($('#event_o_gallery_ids').val() || '').split(',').filter(Boolean).map(Number)
        };

        // On new posts, preselect single-option taxonomies
        if (isNew) {
            if (D.preselectCats === '1' && categories.length === 1 && !wizardState.categories.length) {
                wizardState.categories = [categories[0].id];
            }
            if (D.preselectVenues === '1' && venues.length === 1 && !wizardState.venues.length) {
                wizardState.venues = [venues[0].id];
            }
            if (D.preselectOrgs === '1' && organizers.length === 1 && !wizardState.organizers.length) {
                wizardState.organizers = [organizers[0].id];
            }
        }

        // Featured image
        if (isGutenberg) {
            var fmId = wp.data.select('core/editor').getEditedPostAttribute('featured_media');
            if (fmId && fmId > 0) {
                featuredImageId = fmId;
                var mediaObj = wp.data.select('core').getMedia(fmId);
                if (mediaObj && mediaObj.source_url) {
                    featuredImageUrl = mediaObj.source_url;
                }
            }
        } else {
            var $thumbId = $('#_thumbnail_id');
            if ($thumbId.length && parseInt($thumbId.val(), 10) > 0) {
                featuredImageId = parseInt($thumbId.val(), 10);
                var $img = $('#postimagediv img');
                if ($img.length) featuredImageUrl = $img.attr('src');
            }
        }

        // Gallery preview URLs
        galleryImages = [];
        wizardState.galleryIds.forEach(function (id) {
            var $item = $('.event-o-gallery-item[data-id="' + id + '"]');
            var src = $item.find('img').attr('src') || '';
            galleryImages.push({ id: id, url: src });
        });
    }

    /* ================================================================
       WRITE WIZARD VALUES BACK INTO REAL FORM FIELDS
       ================================================================ */
    function writeValuesToForm() {
        var s = wizardState;

        if (isGutenberg) {
            // Title + Content + Taxonomies + Featured image via Gutenberg stores
            var postUpdate = { title: s.title };

            // Content: insert as paragraph block
            if (s.description) {
                postUpdate.content = s.description;
            }

            // Taxonomies
            postUpdate.event_o_category  = s.categories;
            postUpdate.event_o_venue     = s.venues;
            postUpdate.event_o_organizer = s.organizers;
            postUpdate.tags              = s.tags;

            // Featured image
            if (featuredImageId > 0) {
                postUpdate.featured_media = featuredImageId;
            }

            wp.data.dispatch('core/editor').editPost(postUpdate);

            // Content as blocks (paragraph)
            if (s.description) {
                var paragraphs = s.description.split('\n').filter(function (p) { return p.trim(); });
                var blocks = paragraphs.map(function (p) {
                    return wp.blocks.createBlock('core/paragraph', { content: p });
                });
                if (blocks.length) {
                    wp.data.dispatch('core/block-editor').resetBlocks(blocks);
                }
            }
        } else {
            // Classic editor fallback
            if ($('#title').length) $('#title').val(s.title).trigger('change');
            if ($('#post_title').length) $('#post_title').val(s.title);
            setEditorContent(s.description);
            setTaxCheckboxes('event_o_categorychecklist', s.categories);
            setTaxCheckboxes('event_o_venuechecklist', s.venues);
            setTaxCheckboxes('event_o_organizerchecklist', s.organizers);
            setClassicTagInput(s.tags);
            if (featuredImageId > 0) {
                setFeaturedImage(featuredImageId);
            }
        }

        // Metabox fields (exist in both Gutenberg and classic editor)
        setVal('#event_o_start_datetime', s.startDate1);
        setVal('#event_o_end_datetime', s.endDate1);
        setVal('#event_o_begin_time', s.beginTime1);
        setVal('#event_o_start_datetime_2', s.startDate2);
        setVal('#event_o_end_datetime_2', s.endDate2);
        setVal('#event_o_begin_time_2', s.beginTime2);
        setVal('#event_o_start_datetime_3', s.startDate3);
        setVal('#event_o_end_datetime_3', s.endDate3);
        setVal('#event_o_begin_time_3', s.beginTime3);

        if (s.startDate2 || s.endDate2 || s.beginTime2) {
            $('#event_o_term_2').removeClass('is-hidden');
        }
        if (s.startDate3 || s.endDate3 || s.beginTime3) {
            $('#event_o_term_3').removeClass('is-hidden');
        }

        setVal('#event_o_price', s.price);
        setVal('#event_o_status', s.status);
        setVal('#event_o_status_label', s.status === 'soldout' ? s.statusLabel : '');
        $('#event_o_status').trigger('change');
        $('#event_o_highlight').prop('checked', !!s.isHighlight).trigger('change');
        setVal('#event_o_highlight_until', s.isHighlight ? s.highlightUntil : '');

        var bandsStr = s.bands.map(function (b) {
            return [b.name, b.spotify, b.bandcamp, b.website].join(' | ');
        }).join('\n');
        setVal('#event_o_bands', bandsStr);

        var gids = galleryImages.map(function (g) { return g.id; });
        setVal('#event_o_gallery_ids', gids.join(','));

        rebuildGalleryPreview();
    }

    /* ================================================================
       STEP DEFINITIONS
       ================================================================ */
    var steps = [
        { key: 'basics',   label: 'Schritt 1 von 5', title: 'Grundlagen',     subtitle: 'Titel, Kategorie, Schlagwörter und Beschreibung', render: renderBasics },
        { key: 'when',     label: 'Schritt 2 von 5', title: 'Wann?',          subtitle: 'Datum, Uhrzeit und optionale Zusatztermine', render: renderWhen },
        { key: 'where',    label: 'Schritt 3 von 5', title: 'Wo & Wer?',      subtitle: 'Veranstaltungsort und Veranstalter', render: renderWhere },
        { key: 'details',  label: 'Schritt 4 von 5', title: 'Details',         subtitle: 'Preis, Status, Bilder und Bands', render: renderDetails },
        { key: 'summary',  label: 'Schritt 5 von 5', title: 'Zusammenfassung', subtitle: 'Überprüfe deine Eingaben', render: renderSummary }
    ];

    var statusLabels = {
        cancelled: 'Abgesagt',
        postponed: 'Verschoben',
        soldout: 'Ausverkauft'
    };

    /* ================================================================
       RENDER STEP
       ================================================================ */
    function renderStep() {
        var step = steps[currentStep];
        var html = '';

        // Progress
        html += '<div class="eo-wizard-progress">';
        for (var i = 0; i < totalSteps; i++) {
            var cls = i < currentStep ? 'is-done' : (i === currentStep ? 'is-active' : '');
            html += '<div class="eo-wizard-progress-step ' + cls + '"></div>';
        }
        html += '</div>';

        // Header
        html += '<div class="eo-wizard-header">';
        html += '<p class="eo-wizard-step-label">' + esc(step.label) + '</p>';
        html += '<h2 class="eo-wizard-title">' + esc(step.title) + '</h2>';
        html += '<p class="eo-wizard-subtitle">' + esc(step.subtitle) + '</p>';
        html += '</div>';

        // Body placeholder
        html += '<div class="eo-wizard-body"></div>';

        // Footer
        html += '<div class="eo-wizard-footer">';
        html += '<div class="eo-wizard-footer-left">';
        html += '<button type="button" class="eo-wizard-btn eo-wizard-btn-skip eo-wizard-close-btn">' + esc(texts.classicEditor || 'Klassischer Editor') + '</button>';
        html += '</div>';
        html += '<div class="eo-wizard-footer-right">';
        if (currentStep > 0) {
            html += '<button type="button" class="eo-wizard-btn eo-wizard-btn-secondary eo-wizard-back-btn">← ' + esc(texts.back || 'Zurück') + '</button>';
        }
        if (currentStep < totalSteps - 1) {
            html += '<button type="button" class="eo-wizard-btn eo-wizard-btn-primary eo-wizard-next-btn">' + esc(texts.next || 'Weiter') + ' →</button>';
        }
        html += '</div>';
        html += '</div>';

        $modal.html(html);

        // Render body content
        step.render($modal.find('.eo-wizard-body'));

        // Bind nav
        $modal.find('.eo-wizard-back-btn').on('click', goBack);
        $modal.find('.eo-wizard-next-btn').on('click', goNext);
        $modal.find('.eo-wizard-close-btn').on('click', function () {
            collectCurrentStep();
            writeValuesToForm();
            closeWizard();
        });
    }

    function goBack() {
        collectCurrentStep();
        currentStep = Math.max(0, currentStep - 1);
        renderStep();
    }

    function goNext() {
        collectCurrentStep();
        if (currentStep === 0 && !wizardState.title.trim()) {
            $modal.find('#eo_wiz_title').addClass('has-error').focus();
            return;
        }
        currentStep = Math.min(totalSteps - 1, currentStep + 1);
        renderStep();
    }

    /* ================================================================
       STEP 1 — BASICS
       ================================================================ */
    function renderBasics($body) {
        var html = '';
        html += '<div class="eo-wizard-field">';
        html += '<label class="eo-wizard-label">Titel <span class="eo-required">*</span></label>';
        html += '<input type="text" id="eo_wiz_title" class="eo-wizard-input" value="' + escAttr(wizardState.title) + '" placeholder="z.B. Open Air Konzert">';
        html += '</div>';

        // Categories
        if (categories.length) {
            html += '<div class="eo-wizard-field">';
            html += '<label class="eo-wizard-label">Kategorie</label>';
            html += '<div class="eo-wizard-check-group">';
            categories.forEach(function (cat) {
                var checked = wizardState.categories.indexOf(cat.id) !== -1 ? ' checked' : '';
                var colorDot = cat.color ? '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' + escAttr(cat.color) + ';margin-right:4px;vertical-align:middle"></span>' : '';
                html += '<label class="eo-wizard-check-item"><input type="checkbox" data-tax="category" value="' + cat.id + '"' + checked + '> ' + colorDot + esc(cat.name) + '</label>';
            });
            html += '</div></div>';
        }

        if (tags.length) {
            html += '<div class="eo-wizard-field">';
            html += '<label class="eo-wizard-label">Schlagwörter</label>';
            html += '<div class="eo-wizard-tag-picker">';
            html += '<input type="text" id="eo_wiz_tag_search" class="eo-wizard-input" value="" placeholder="Schlagwort suchen…">';
            html += '<input type="hidden" id="eo_wiz_tag_ids" value="' + escAttr((wizardState.tags || []).join(',')) + '">';
            html += '<div class="eo-wizard-tag-selected" id="eo_wiz_tag_selected"></div>';
            html += '<div class="eo-wizard-tag-quick-wrap">';
            html += '<span class="eo-wizard-hint eo-wizard-tag-hint">Häufigste Schlagwörter</span>';
            html += '<div class="eo-wizard-tag-quick" id="eo_wiz_tag_quick"></div>';
            html += '</div>';
            html += '<div class="eo-wizard-tag-results" id="eo_wiz_tag_results"></div>';
            html += '</div></div>';
        }

        // Description
        html += '<div class="eo-wizard-field">';
        html += '<label class="eo-wizard-label">Beschreibung</label>';
        html += '<textarea id="eo_wiz_desc" class="eo-wizard-textarea" rows="5" placeholder="Kurze Beschreibung des Events…">' + esc(wizardState.description) + '</textarea>';
        html += '</div>';

        $body.html(html);

        if (tags.length) {
            initTagPicker($body);
        }

        // Auto-focus title
        setTimeout(function () { $body.find('#eo_wiz_title').focus(); }, 100);
    }

    /* ================================================================
       STEP 2 — WANN
       ================================================================ */
    function renderWhen($body) {
        var html = '';

        // Termin 1
        html += buildTerminBlock(1, 'Termin 1', wizardState.startDate1, wizardState.endDate1, wizardState.beginTime1, false);

        // Termin 2
        var has2 = wizardState.startDate2 || wizardState.endDate2 || wizardState.beginTime2;
        html += '<div id="eo_wiz_term2_wrap" class="eo-wizard-field" style="' + (has2 ? '' : 'display:none') + '">';
        html += buildTerminFields(2, 'Termin 2 (optional)', wizardState.startDate2, wizardState.endDate2, wizardState.beginTime2, true);
        html += '</div>';

        // Termin 3
        var has3 = wizardState.startDate3 || wizardState.endDate3 || wizardState.beginTime3;
        html += '<div id="eo_wiz_term3_wrap" class="eo-wizard-field" style="' + (has3 ? '' : 'display:none') + '">';
        html += buildTerminFields(3, 'Termin 3 (optional)', wizardState.startDate3, wizardState.endDate3, wizardState.beginTime3, true);
        html += '</div>';

        // Add term buttons
        html += '<div id="eo_wiz_add_term_btns" class="eo-wizard-field">';
        html += '<button type="button" class="eo-wizard-add-term-btn" data-term="2" style="' + (has2 ? 'display:none' : '') + '">+ Weiteren Termin hinzufügen</button> ';
        html += '<button type="button" class="eo-wizard-add-term-btn" data-term="3" style="' + (has2 && !has3 ? '' : 'display:none') + '">+ Termin 3 hinzufügen</button>';
        html += '</div>';

        $body.html(html);

        // "Add term" click handlers
        $body.on('click', '.eo-wizard-add-term-btn', function () {
            var num = $(this).data('term');
            $('#eo_wiz_term' + num + '_wrap').show();
            $(this).hide();
            if (num === 2) {
                $body.find('.eo-wizard-add-term-btn[data-term="3"]').show();
            }
        });

        // "Remove term" click handlers
        $body.on('click', '.eo-wizard-term-remove', function () {
            var num = $(this).data('term');
            var $wrap = $('#eo_wiz_term' + num + '_wrap');
            $wrap.find('input').val('');
            $wrap.hide();
            if (num === 2) {
                var $w3 = $('#eo_wiz_term3_wrap');
                $w3.find('input').val('');
                $w3.hide();
                $body.find('.eo-wizard-add-term-btn[data-term="3"]').hide();
                $body.find('.eo-wizard-add-term-btn[data-term="2"]').show();
            } else {
                $body.find('.eo-wizard-add-term-btn[data-term="3"]').hide();
                var has2 = $('#eo_wiz_term2_wrap').is(':visible');
                if (has2) {
                    $body.find('.eo-wizard-add-term-btn[data-term="3"]').show();
                }
            }
        });
    }

    function buildTerminBlock(num, legend, startVal, endVal, beginVal, removable) {
        return '<div class="eo-wizard-field">' + buildTerminFields(num, legend, startVal, endVal, beginVal, removable) + '</div>';
    }

    function buildTerminFields(num, legend, startVal, endVal, beginVal, removable) {
        var html = '<label class="eo-wizard-label">' + esc(legend);
        if (removable) {
            html += ' <button type="button" class="eo-wizard-term-remove" data-term="' + num + '" style="float:right;background:none;border:none;color:#d63638;cursor:pointer;font-size:12px">✕ Entfernen</button>';
        }
        html += '</label>';
        html += '<div class="eo-wizard-row">';
        html += '<div class="eo-wizard-field"><label class="eo-wizard-label" style="font-size:12px;font-weight:400">Von</label><input type="datetime-local" class="eo-wizard-input eo-wiz-start" data-slot="' + num + '" value="' + escAttr(startVal) + '"></div>';
        html += '<div class="eo-wizard-field"><label class="eo-wizard-label" style="font-size:12px;font-weight:400">Bis</label><input type="datetime-local" class="eo-wizard-input eo-wiz-end" data-slot="' + num + '" value="' + escAttr(endVal) + '"></div>';
        html += '<div class="eo-wizard-field" style="max-width:120px"><label class="eo-wizard-label" style="font-size:12px;font-weight:400">Beginn <span class="eo-wizard-begin-hint" title="Nur ausfüllen wenn Beginn von Einlass abweicht (z.B. Einlass 19 Uhr, Beginn 20 Uhr). Leer lassen wenn nicht benötigt.">ⓘ</span></label><input type="time" class="eo-wizard-input eo-wiz-begin" data-slot="' + num + '" value="' + escAttr(beginVal) + '"></div>';
        html += '</div>';
        return html;
    }

    /* ================================================================
       STEP 3 — WO & WER
       ================================================================ */
    function renderWhere($body) {
        var html = '';

        if (venues.length) {
            html += '<div class="eo-wizard-field">';
            html += '<label class="eo-wizard-label">Veranstaltungsort</label>';
            html += '<div class="eo-wizard-check-group">';
            venues.forEach(function (v) {
                var checked = wizardState.venues.indexOf(v.id) !== -1 ? ' checked' : '';
                html += '<label class="eo-wizard-check-item"><input type="checkbox" data-tax="venue" value="' + v.id + '"' + checked + '> ' + esc(v.name) + '</label>';
            });
            html += '</div></div>';
        }

        if (organizers.length) {
            html += '<div class="eo-wizard-field">';
            html += '<label class="eo-wizard-label">Veranstalter</label>';
            html += '<div class="eo-wizard-check-group">';
            organizers.forEach(function (o) {
                var checked = wizardState.organizers.indexOf(o.id) !== -1 ? ' checked' : '';
                html += '<label class="eo-wizard-check-item"><input type="checkbox" data-tax="organizer" value="' + o.id + '"' + checked + '> ' + esc(o.name) + '</label>';
            });
            html += '</div></div>';
        }

        if (!venues.length && !organizers.length) {
            html += '<div class="eo-wizard-field"><p style="color:#757575">Noch keine Orte oder Veranstalter angelegt. Du kannst sie jederzeit über die Taxonomie-Verwaltung ergänzen.</p></div>';
        }

        $body.html(html);
    }

    /* ================================================================
       STEP 4 — DETAILS
       ================================================================ */
    function renderDetails($body) {
        var html = '';

        // Price + Status row
        html += '<div class="eo-wizard-row">';
        html += '<div class="eo-wizard-field"><label class="eo-wizard-label">Preis</label><input type="text" id="eo_wiz_price" class="eo-wizard-input" value="' + escAttr(wizardState.price) + '" placeholder="z.B. Frei / 5 €"></div>';
        html += '<div class="eo-wizard-field"><label class="eo-wizard-label">Status</label><select id="eo_wiz_status" class="eo-wizard-select"><option value="">Normal</option><option value="cancelled"' + (wizardState.status === 'cancelled' ? ' selected' : '') + '>Cancelled</option><option value="postponed"' + (wizardState.status === 'postponed' ? ' selected' : '') + '>Postponed</option><option value="soldout"' + (wizardState.status === 'soldout' ? ' selected' : '') + '>Sold out</option></select></div>';
        html += '</div>';
        html += '<div id="eo_wiz_status_label_wrap" class="eo-wizard-field" style="' + (wizardState.status === 'soldout' ? '' : 'display:none;') + '">';
        html += '<label class="eo-wizard-label">Text für Ausverkauft-Badge</label>';
        html += '<input type="text" id="eo_wiz_status_label" class="eo-wizard-input" value="' + escAttr(wizardState.statusLabel || '') + '" placeholder="z.B. Restkarten an der Abendkasse">';
        html += '<span class="eo-wizard-hint">Nur sichtbar, wenn der Status auf Sold out steht. Leer = Standardtext.</span>';
        html += '</div>';

        html += '<div class="eo-wizard-field">';
        html += '<label class="eo-wizard-check-item"><input type="checkbox" id="eo_wiz_is_highlight"' + (wizardState.isHighlight ? ' checked' : '') + '> Event als Highlight im Hero bevorzugen</label>';
        html += '<div id="eo_wiz_highlight_until_wrap" style="margin-top:12px;' + (wizardState.isHighlight ? '' : 'display:none;') + '">';
        html += '<label class="eo-wizard-label">Highlight bis</label>';
        html += '<input type="datetime-local" id="eo_wiz_highlight_until" class="eo-wizard-input" value="' + escAttr(wizardState.highlightUntil) + '">';
        html += '<span class="eo-wizard-hint">Leer lassen, damit das Highlight ohne Ablaufdatum aktiv bleibt.</span>';
        html += '</div>';
        html += '</div>';

        // Featured image
        html += '<div class="eo-wizard-field">';
        html += '<label class="eo-wizard-label">Beitragsbild</label>';
        html += '<div id="eo_wiz_featured_preview" class="eo-wizard-image-preview">';
        if (featuredImageId && featuredImageUrl) {
            html += thumbHtml(featuredImageId, featuredImageUrl, 'featured');
        }
        html += '</div>';
        html += '<button type="button" class="eo-wizard-upload-btn" id="eo_wiz_featured_btn">📷 Beitragsbild wählen</button>';
        html += '</div>';

        // Gallery
        html += '<div class="eo-wizard-field">';
        html += '<label class="eo-wizard-label">Galerie (max. 2 zusätzliche Bilder)</label>';
        html += '<span class="eo-wizard-hint">Für optionalen Crossfade neben dem Beitragsbild.</span>';
        html += '<div id="eo_wiz_gallery_preview" class="eo-wizard-image-preview">';
        galleryImages.forEach(function (img) {
            html += thumbHtml(img.id, img.url, 'gallery');
        });
        html += '</div>';
        html += '<button type="button" class="eo-wizard-upload-btn" id="eo_wiz_gallery_btn">📷 Galerie-Bilder wählen</button>';
        html += '</div>';

        // Bands
        html += '<div class="eo-wizard-field">';
        html += '<label class="eo-wizard-label">Bands / Artists</label>';
        html += '<span class="eo-wizard-hint">Pro Zeile: Name, Spotify, Bandcamp, Website (alles optional außer Name)</span>';
        html += '<div id="eo_wiz_bands" class="eo-wizard-band-rows">';
        if (wizardState.bands.length) {
            wizardState.bands.forEach(function (b, i) {
                html += bandRowHtml(i, b);
            });
        } else {
            html += bandRowHtml(0, { name: '', spotify: '', bandcamp: '', website: '' });
        }
        html += '</div>';
        html += '<button type="button" class="eo-wizard-band-add" id="eo_wiz_band_add">+ Band hinzufügen</button>';
        html += '</div>';

        $body.html(html);

        // Featured image upload
        $body.on('click', '#eo_wiz_featured_btn', function (e) {
            e.preventDefault();
            pickImage(false, function (att) {
                featuredImageId  = att.id;
                featuredImageUrl = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
                var $p = $body.find('#eo_wiz_featured_preview');
                $p.html(thumbHtml(att.id, featuredImageUrl, 'featured'));
            });
        });

        // Gallery upload
        $body.on('click', '#eo_wiz_gallery_btn', function (e) {
            e.preventDefault();
            if (galleryImages.length >= 2) {
                alert('Maximal 2 Galerie-Bilder möglich.');
                return;
            }
            pickImage(true, function (selected) {
                var arr = Array.isArray(selected) ? selected : [selected];
                arr.forEach(function (att) {
                    if (galleryImages.length >= 2) return;
                    if (galleryImages.some(function (g) { return g.id === att.id; })) return;
                    var url = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
                    galleryImages.push({ id: att.id, url: url });
                    $body.find('#eo_wiz_gallery_preview').append(thumbHtml(att.id, url, 'gallery'));
                });
            });
        });

        // Remove images
        $body.on('click', '.eo-wizard-image-remove', function (e) {
            e.preventDefault();
            var $thumb = $(this).closest('.eo-wizard-image-thumb');
            var id = parseInt($thumb.data('id'), 10);
            var type = $thumb.data('type');
            $thumb.remove();
            if (type === 'featured') {
                featuredImageId = 0;
                featuredImageUrl = '';
            } else {
                galleryImages = galleryImages.filter(function (g) { return g.id !== id; });
            }
        });

        // Add band row
        $body.on('click', '#eo_wiz_band_add', function () {
            var idx = $body.find('.eo-wizard-band-row').length;
            $body.find('#eo_wiz_bands').append(bandRowHtml(idx, { name: '', spotify: '', bandcamp: '', website: '' }));
        });

        // Remove band row
        $body.on('click', '.eo-wizard-band-remove', function () {
            $(this).closest('.eo-wizard-band-row').remove();
        });

        $body.on('change', '#eo_wiz_is_highlight', function () {
            var checked = $(this).is(':checked');
            var $wrap = $body.find('#eo_wiz_highlight_until_wrap');
            $wrap.toggle(checked);
            if (!checked) {
                $wrap.find('#eo_wiz_highlight_until').val('');
            }
        });

        $body.on('change', '#eo_wiz_status', function () {
            var isSoldOut = $(this).val() === 'soldout';
            var $wrap = $body.find('#eo_wiz_status_label_wrap');
            $wrap.toggle(isSoldOut);
            if (!isSoldOut) {
                $wrap.find('#eo_wiz_status_label').val('');
            }
        });
    }

    function thumbHtml(id, url, type) {
        return '<div class="eo-wizard-image-thumb" data-id="' + id + '" data-type="' + type + '"><img src="' + escAttr(url) + '" alt=""><button type="button" class="eo-wizard-image-remove">×</button></div>';
    }

    function bandRowHtml(idx, b) {
        var html = '<div class="eo-wizard-band-row">';
        html += '<input type="text" class="eo-wizard-input eo-wiz-band-name" placeholder="Band-Name" value="' + escAttr(b.name) + '">';
        html += '<input type="url" class="eo-wizard-input eo-wiz-band-spotify" placeholder="Spotify URL" value="' + escAttr(b.spotify) + '">';
        html += '<input type="url" class="eo-wizard-input eo-wiz-band-bandcamp" placeholder="Bandcamp URL" value="' + escAttr(b.bandcamp) + '">';
        html += '<input type="url" class="eo-wizard-input eo-wiz-band-website" placeholder="Website URL" value="' + escAttr(b.website) + '">';
        html += '<button type="button" class="eo-wizard-band-remove">✕</button>';
        html += '</div>';
        return html;
    }

    /* ================================================================
       STEP 5 — SUMMARY
       ================================================================ */
    function renderSummary($body) {
        collectCurrentStep(); // make sure we have latest data

        var s = wizardState;
        var html = '<div class="eo-wizard-summary">';

        // Basics
        html += summaryCard('Grundlagen', 0,
            '<p><strong>' + esc(s.title || '—') + '</strong></p>' +
            (s.description ? '<p style="color:#555;font-size:13px">' + esc(truncate(s.description, 120)) + '</p>' : '') +
            catLabels(s.categories) +
            tagLabels(s.tags)
        );

        // Wann
        var whenHtml = buildDateSummary(1, s.startDate1, s.endDate1, s.beginTime1);
        whenHtml += buildDateSummary(2, s.startDate2, s.endDate2, s.beginTime2);
        whenHtml += buildDateSummary(3, s.startDate3, s.endDate3, s.beginTime3);
        if (!whenHtml) whenHtml = '<p style="color:#999">Kein Datum gesetzt</p>';
        html += summaryCard('Wann', 1, whenHtml);

        // Wo & Wer
        var whereHtml = '';
        if (s.venues.length)     whereHtml += '<p>🏠 ' + termNames(venues, s.venues) + '</p>';
        if (s.organizers.length) whereHtml += '<p>👤 ' + termNames(organizers, s.organizers) + '</p>';
        if (!whereHtml)          whereHtml = '<p style="color:#999">—</p>';
        html += summaryCard('Wo & Wer', 2, whereHtml);

        // Details
        var detailHtml = '';
        if (s.price)  detailHtml += '<p>💰 ' + esc(s.price) + '</p>';
        if (s.status) {
            var statusText = s.status === 'soldout' && s.statusLabel ? s.statusLabel : getStatusLabel(s.status);
            detailHtml += '<p>📌 ' + esc(statusText || s.status) + '</p>';
        }
        if (s.isHighlight) {
            detailHtml += '<p>⭐ Highlight aktiv' + (s.highlightUntil ? ' bis ' + esc(formatDT(s.highlightUntil)) : '') + '</p>';
        }
        if (featuredImageId) detailHtml += '<p>🖼️ Beitragsbild gesetzt</p>';
        if (galleryImages.length) detailHtml += '<p>🖼️ ' + galleryImages.length + ' Galerie-Bild(er)</p>';
        if (s.bands.filter(function (b) { return b.name; }).length) detailHtml += '<p>🎵 ' + s.bands.filter(function (b) { return b.name; }).length + ' Band(s)</p>';
        if (!detailHtml) detailHtml = '<p style="color:#999">—</p>';
        html += summaryCard('Details', 3, detailHtml);

        html += '</div>';

        // Action buttons
        html += '<div style="display:flex;gap:10px;margin-top:20px;justify-content:center">';
        html += '<button type="button" class="eo-wizard-btn eo-wizard-btn-draft" id="eo_wiz_save_draft">' + esc(texts.saveDraft || 'Entwurf speichern') + '</button>';
        html += '<button type="button" class="eo-wizard-btn eo-wizard-btn-publish" id="eo_wiz_publish">' + esc(texts.publish || 'Veröffentlichen') + '</button>';
        html += '</div>';

        $body.html(html);

        // Edit card → jump to step
        $body.on('click', '.eo-wizard-summary-edit', function () {
            var step = parseInt($(this).data('step'), 10);
            currentStep = step;
            renderStep();
        });

        // Save actions
        $body.on('click', '#eo_wiz_save_draft', function () {
            writeValuesToForm();
            closeWizard();
            if (isGutenberg) {
                wp.data.dispatch('core/editor').savePost();
            } else {
                var $draftBtn = $('#save-post');
                if ($draftBtn.length) {
                    $draftBtn.trigger('click');
                } else {
                    $('#post').trigger('submit');
                }
            }
        });

        $body.on('click', '#eo_wiz_publish', function () {
            writeValuesToForm();
            closeWizard();
            if (isGutenberg) {
                var newStatus = canPublish ? 'publish' : 'pending';
                wp.data.dispatch('core/editor').editPost({ status: newStatus });
                wp.data.dispatch('core/editor').savePost();
            } else {
                var $pubBtn = $('#publish');
                if ($pubBtn.length) {
                    $pubBtn.trigger('click');
                }
            }
        });
    }

    function summaryCard(title, stepIdx, content) {
        return '<div class="eo-wizard-summary-card"><h4>' + esc(title) + '</h4>' + content + '<button type="button" class="eo-wizard-summary-edit" data-step="' + stepIdx + '">Bearbeiten</button></div>';
    }

    function buildDateSummary(num, start, end, begin) {
        if (!start && !end) return '';
        var label = 'Termin ' + num + ': ';
        var parts = [];
        if (start) parts.push(formatDT(start));
        if (end)   parts.push(' – ' + formatDT(end));
        if (begin) parts.push(' (Beginn: ' + begin + ')');
        return '<p>' + label + parts.join('') + '</p>';
    }

    /* ================================================================
       COLLECT STEP DATA
       ================================================================ */
    function collectCurrentStep() {
        if (!$modal) return;
        var $b = $modal.find('.eo-wizard-body');

        switch (currentStep) {
            case 0: // Basics
                wizardState.title = $b.find('#eo_wiz_title').val() || '';
                wizardState.description = $b.find('#eo_wiz_desc').val() || '';
                wizardState.categories = [];
                $b.find('[data-tax="category"]:checked').each(function () {
                    wizardState.categories.push(parseInt($(this).val(), 10));
                });
                wizardState.tags = getSelectedTagIds($b);
                break;

            case 1: // When
                $b.find('.eo-wiz-start').each(function () {
                    var slot = $(this).data('slot');
                    wizardState['startDate' + slot] = $(this).val() || '';
                });
                $b.find('.eo-wiz-end').each(function () {
                    var slot = $(this).data('slot');
                    wizardState['endDate' + slot] = $(this).val() || '';
                });
                $b.find('.eo-wiz-begin').each(function () {
                    var slot = $(this).data('slot');
                    wizardState['beginTime' + slot] = $(this).val() || '';
                });
                break;

            case 2: // Where
                wizardState.venues = [];
                $b.find('[data-tax="venue"]:checked').each(function () {
                    wizardState.venues.push(parseInt($(this).val(), 10));
                });
                wizardState.organizers = [];
                $b.find('[data-tax="organizer"]:checked').each(function () {
                    wizardState.organizers.push(parseInt($(this).val(), 10));
                });
                break;

            case 3: // Details
                wizardState.price = $b.find('#eo_wiz_price').val() || '';
                wizardState.status = $b.find('#eo_wiz_status').val() || '';
                wizardState.statusLabel = wizardState.status === 'soldout' ? ($b.find('#eo_wiz_status_label').val() || '') : '';
                wizardState.isHighlight = $b.find('#eo_wiz_is_highlight').is(':checked');
                wizardState.highlightUntil = wizardState.isHighlight ? ($b.find('#eo_wiz_highlight_until').val() || '') : '';
                wizardState.bands = [];
                $b.find('.eo-wizard-band-row').each(function () {
                    var $r = $(this);
                    var name = $r.find('.eo-wiz-band-name').val() || '';
                    if (!name && !$r.find('.eo-wiz-band-spotify').val() && !$r.find('.eo-wiz-band-bandcamp').val() && !$r.find('.eo-wiz-band-website').val()) return;
                    wizardState.bands.push({
                        name:     name,
                        spotify:  $r.find('.eo-wiz-band-spotify').val() || '',
                        bandcamp: $r.find('.eo-wiz-band-bandcamp').val() || '',
                        website:  $r.find('.eo-wiz-band-website').val() || ''
                    });
                });
                break;
        }
    }

    /* ================================================================
       HELPERS
       ================================================================ */
    function esc(str) {
        if (!str) return '';
        var el = document.createElement('span');
        el.textContent = str;
        return el.innerHTML;
    }

    function escAttr(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function setVal(sel, val) {
        var $el = $(sel);
        if ($el.length) $el.val(val);
    }

    function truncate(str, len) {
        if (!str) return '';
        return str.length > len ? str.substring(0, len) + '…' : str;
    }

    function formatDT(val) {
        if (!val) return '';
        // val is "YYYY-MM-DDTHH:MM"
        var parts = val.split('T');
        if (parts.length < 2) return val;
        var d = parts[0].split('-');
        return d[2] + '.' + d[1] + '.' + d[0] + ' ' + parts[1];
    }

    function getStatusLabel(status) {
        return statusLabels[status] || status || '';
    }

    function parseBands(raw) {
        if (!raw.trim()) return [];
        return raw.trim().split('\n').map(function (line) {
            var parts = line.split('|').map(function (s) { return s.trim(); });
            return {
                name:     parts[0] || '',
                spotify:  parts[1] || '',
                bandcamp: parts[2] || '',
                website:  parts[3] || ''
            };
        });
    }

    function getEditorContent() {
        if (isGutenberg) {
            var content = wp.data.select('core/editor').getEditedPostAttribute('content') || '';
            if (!content) return '';
            var tmp = document.createElement('div');
            tmp.innerHTML = content;
            return tmp.textContent || tmp.innerText || '';
        }
        // Classic editor
        var $content = $('#content');
        if ($content.length) {
            if (typeof tinymce !== 'undefined') {
                var ed = tinymce.get('content');
                if (ed && !ed.isHidden()) {
                    return ed.getContent({ format: 'text' });
                }
            }
            return $content.val() || '';
        }
        return '';
    }

    function setEditorContent(text) {
        var $content = $('#content');
        if ($content.length) {
            $content.val(text);
            if (typeof tinymce !== 'undefined') {
                var ed = tinymce.get('content');
                if (ed) {
                    ed.setContent(text ? '<p>' + esc(text).replace(/\n/g, '</p><p>') + '</p>' : '');
                }
            }
        }
    }

    function getTitle() {
        if (isGutenberg) {
            return wp.data.select('core/editor').getEditedPostAttribute('title') || '';
        }
        return $('#title').val() || $('#post_title').val() || '';
    }

    function getTaxIds(taxonomy) {
        if (isGutenberg) {
            return (wp.data.select('core/editor').getEditedPostAttribute(taxonomy) || []).slice();
        }
        var checklistMap = {
            'event_o_category':  'event_o_categorychecklist',
            'event_o_venue':     'event_o_venuechecklist',
            'event_o_organizer': 'event_o_organizerchecklist'
        };
        return getCheckedTaxIds(checklistMap[taxonomy] || '');
    }

    function getTagIds() {
        if (isGutenberg) {
            return (wp.data.select('core/editor').getEditedPostAttribute('tags') || []).slice();
        }

        var raw = ($('#tax-input-post_tag').val() || '').trim();
        if (!raw) return [];

        var wanted = raw.split(',').map(function (name) {
            return name.trim().toLowerCase();
        }).filter(Boolean);
        var ids = [];

        tags.forEach(function (tag) {
            if (wanted.indexOf(String(tag.name).trim().toLowerCase()) !== -1) {
                ids.push(tag.id);
            }
        });

        return ids;
    }

    function getCheckedTaxIds(checklistId) {
        var ids = [];
        $('#' + checklistId + ' input:checked').each(function () {
            var val = parseInt($(this).val(), 10);
            if (val > 0) ids.push(val);
        });
        return ids;
    }

    function initTagPicker($body) {
        renderTagPicker($body);

        $body.on('input', '#eo_wiz_tag_search', function () {
            renderTagPicker($body);
        });

        $body.on('keydown', '#eo_wiz_tag_search', function (e) {
            if (e.key !== 'Enter') return;

            var tagId = getFirstSelectableTagId($body);
            if (!tagId) return;

            e.preventDefault();

            var selectedIds = getSelectedTagIds($body);
            if (selectedIds.indexOf(tagId) === -1) {
                selectedIds.push(tagId);
                setSelectedTagIds($body, selectedIds);
            }

            $(this).val('');
            renderTagPicker($body);
        });

        $body.on('click', '.eo-wizard-tag-option', function () {
            var tagId = parseInt($(this).attr('data-tag-id'), 10);
            if (!tagId) return;

            var selectedIds = getSelectedTagIds($body);
            var idx = selectedIds.indexOf(tagId);
            if (idx === -1) {
                selectedIds.push(tagId);
            } else {
                selectedIds.splice(idx, 1);
            }

            setSelectedTagIds($body, selectedIds);
            renderTagPicker($body);
            $body.find('#eo_wiz_tag_search').focus();
        });

        $body.on('click', '.eo-wizard-tag-chip-remove', function () {
            var tagId = parseInt($(this).attr('data-tag-id'), 10);
            if (!tagId) return;

            var selectedIds = getSelectedTagIds($body).filter(function (id) {
                return id !== tagId;
            });

            setSelectedTagIds($body, selectedIds);
            renderTagPicker($body);
        });
    }

    function renderTagPicker($body) {
        var selectedIds = getSelectedTagIds($body);
        var query = ($body.find('#eo_wiz_tag_search').val() || '').trim().toLowerCase();
        var quickTags = getTopTags(6);
        var resultTags = query ? getMatchingTags(query, selectedIds, 8) : [];

        $body.find('#eo_wiz_tag_selected').html(buildSelectedTagMarkup(selectedIds));
        $body.find('#eo_wiz_tag_quick').html(buildTagOptionMarkup(quickTags, selectedIds));

        if (!query) {
            $body.find('#eo_wiz_tag_results').html('');
            return;
        }

        if (!resultTags.length) {
            $body.find('#eo_wiz_tag_results').html('<div class="eo-wizard-tag-empty">Keine passenden Schlagwörter gefunden.</div>');
            return;
        }

        $body.find('#eo_wiz_tag_results').html(
            '<span class="eo-wizard-hint eo-wizard-tag-hint">Suchergebnisse</span>' +
            '<div class="eo-wizard-tag-search-list">' + buildTagOptionMarkup(resultTags, selectedIds) + '</div>'
        );
    }

    function getSelectedTagIds($body) {
        var raw = ($body.find('#eo_wiz_tag_ids').val() || '').trim();
        if (!raw) return [];

        return raw.split(',').map(function (value) {
            return parseInt(value, 10);
        }).filter(function (value, index, values) {
            return !isNaN(value) && value > 0 && values.indexOf(value) === index;
        });
    }

    function setSelectedTagIds($body, ids) {
        $body.find('#eo_wiz_tag_ids').val(ids.join(','));
    }

    function getTopTags(limit) {
        return tags.slice().sort(function (a, b) {
            var countDiff = getTagCount(b) - getTagCount(a);
            if (countDiff !== 0) return countDiff;
            return String(a.name || '').localeCompare(String(b.name || ''), 'de', { sensitivity: 'base' });
        }).slice(0, limit);
    }

    function getFirstSelectableTagId($body) {
        var selectedIds = getSelectedTagIds($body);
        var query = ($body.find('#eo_wiz_tag_search').val() || '').trim().toLowerCase();
        var source = query ? getMatchingTags(query, selectedIds, 1) : getTopTags(6).filter(function (tag) {
            return selectedIds.indexOf(tag.id) === -1;
        }).slice(0, 1);

        if (!source.length) {
            return 0;
        }

        return source[0].id || 0;
    }

    function getMatchingTags(query, selectedIds, limit) {
        return tags.filter(function (tag) {
            if (selectedIds.indexOf(tag.id) !== -1) return false;
            return String(tag.name || '').toLowerCase().indexOf(query) !== -1;
        }).sort(function (a, b) {
            var countDiff = getTagCount(b) - getTagCount(a);
            if (countDiff !== 0) return countDiff;
            return String(a.name || '').localeCompare(String(b.name || ''), 'de', { sensitivity: 'base' });
        }).slice(0, limit);
    }

    function getTagCount(tag) {
        var count = parseInt(tag && tag.count ? tag.count : 0, 10);
        return isNaN(count) ? 0 : count;
    }

    function buildSelectedTagMarkup(selectedIds) {
        if (!selectedIds.length) {
            return '<div class="eo-wizard-tag-empty">Noch keine Schlagwörter ausgewählt.</div>';
        }

        var selectedTags = tags.filter(function (tag) {
            return selectedIds.indexOf(tag.id) !== -1;
        }).sort(function (a, b) {
            return selectedIds.indexOf(a.id) - selectedIds.indexOf(b.id);
        });

        return selectedTags.map(function (tag) {
            return '<span class="eo-wizard-tag-chip">' +
                '<span class="eo-wizard-tag-chip-label">' + esc(tag.name) + '</span>' +
                '<button type="button" class="eo-wizard-tag-chip-remove" data-tag-id="' + tag.id + '" aria-label="' + escAttr(tag.name + ' entfernen') + '">×</button>' +
            '</span>';
        }).join('');
    }

    function buildTagOptionMarkup(list, selectedIds) {
        return list.map(function (tag) {
            var active = selectedIds.indexOf(tag.id) !== -1 ? ' is-active' : '';
            return '<button type="button" class="eo-wizard-tag-option' + active + '" data-tag-id="' + tag.id + '">' + esc(tag.name) + '</button>';
        }).join('');
    }

    function setTaxCheckboxes(checklistId, ids) {
        var $list = $('#' + checklistId);
        if (!$list.length) return;
        $list.find('input[type="checkbox"]').each(function () {
            var val = parseInt($(this).val(), 10);
            $(this).prop('checked', ids.indexOf(val) !== -1);
        });
    }

    function setClassicTagInput(ids) {
        var $field = $('#tax-input-post_tag');
        if (!$field.length) return;

        var names = [];
        tags.forEach(function (tag) {
            if (ids.indexOf(tag.id) !== -1) {
                names.push(tag.name);
            }
        });

        $field.val(names.join(', ')).trigger('change');
    }

    function catLabels(ids) {
        if (!ids.length) return '';
        var names = [];
        categories.forEach(function (c) {
            if (ids.indexOf(c.id) !== -1) names.push(esc(c.name));
        });
        return names.length ? '<p style="font-size:12px;color:#757575">Kategorien: ' + names.join(', ') + '</p>' : '';
    }

    function tagLabels(ids) {
        if (!ids.length) return '';
        var names = [];
        tags.forEach(function (tag) {
            if (ids.indexOf(tag.id) !== -1) names.push(esc(tag.name));
        });
        return names.length ? '<p style="font-size:12px;color:#757575">Schlagwörter: ' + names.join(', ') + '</p>' : '';
    }

    function termNames(list, ids) {
        var names = [];
        list.forEach(function (t) {
            if (ids.indexOf(t.id) !== -1) names.push(esc(t.name));
        });
        return names.join(', ');
    }

    function pickImage(multiple, callback) {
        var frame = wp.media({
            title: 'Bild wählen',
            button: { text: 'Übernehmen' },
            multiple: multiple,
            library: { type: 'image' }
        });
        frame.on('select', function () {
            var sel = frame.state().get('selection').toArray();
            if (multiple) {
                callback(sel.map(function (m) { return m.toJSON(); }));
            } else {
                callback(sel[0].toJSON());
            }
        });
        frame.open();
    }

    function setFeaturedImage(id) {
        // WP stores featured image in #_thumbnail_id
        var $field = $('#_thumbnail_id');
        if ($field.length) {
            $field.val(id);
        }
        // Trigger WP's featured image AJAX update
        if (typeof wp !== 'undefined' && wp.media && wp.media.featuredImage) {
            wp.media.featuredImage.set(id);
        }
    }

    function rebuildGalleryPreview() {
        var $preview = $('#event_o_gallery_preview');
        if (!$preview.length) return;
        $preview.empty();
        galleryImages.forEach(function (img) {
            var html = '<div class="event-o-gallery-item" data-id="' + img.id + '" style="position:relative">';
            html += '<img src="' + escAttr(img.url) + '" alt="" style="width:90px;height:90px;object-fit:cover;border:1px solid #ddd;border-radius:4px;display:block">';
            html += '<button type="button" class="button-link-delete event-o-gallery-remove" style="position:absolute;top:2px;right:4px;font-size:16px;line-height:1;text-decoration:none">×</button>';
            html += '</div>';
            $preview.append(html);
        });
    }

})(jQuery);
