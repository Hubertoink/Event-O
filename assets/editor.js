(function (wp) {
    var __ = wp.i18n.__;
    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var useEffect = wp.element.useEffect;
    var useRef = wp.element.useRef;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var ToggleControl = wp.components.ToggleControl;
    var RangeControl = wp.components.RangeControl;
    var TextControl = wp.components.TextControl;
    var ColorPicker = wp.components.ColorPicker;
    var Button = wp.components.Button;
    var Dropdown = wp.components.Dropdown;
    var SelectControl = wp.components.SelectControl;
    var TabPanel = wp.components.TabPanel;
    var GradientPicker = wp.components.GradientPicker || wp.components.__experimentalGradientPicker;
    var ServerSideRender = wp.serverSideRender;

    function TaxHelp(label) {
        return el('p', { style: { marginTop: '8px', color: '#666', fontSize: '12px' } }, label);
    }

    function ColorControl(props) {
        var label = props.label;
        var value = props.value;
        var onChange = props.onChange;

        return el('div', { style: { marginBottom: '16px' } },
            el('label', { style: { display: 'block', marginBottom: '8px', fontWeight: '500' } }, label),
            el(ColorPicker, {
                color: value || '#4f6b3a',
                onChangeComplete: function (c) { onChange(c.hex); },
                disableAlpha: true
            }),
            value && el(Button, {
                isSmall: true,
                variant: 'secondary',
                onClick: function () { onChange(''); },
                style: { marginTop: '8px' }
            }, __('Reset to default', 'event-o'))
        );
    }

    function HighlightBadgePanel(a, setAttributes, options) {
        var showToggle = !options || options.showToggle !== false;
        var showPriority = !!(options && options.showPriority);
        var infoText = options && options.infoText ? options.infoText : '';
        var previewValue = (a.highlightGradient || '').trim() || (a.highlightColor || '').trim() || '#e8364f';
        var isGradient = previewValue.indexOf('gradient') !== -1;
        var gradientPresets = [
            { name: __('Sunset', 'event-o'), gradient: 'linear-gradient(135deg, #ff4d6d 0%, #ff8a5b 100%)' },
            { name: __('Gold Rush', 'event-o'), gradient: 'linear-gradient(135deg, #f7b733 0%, #fc4a1a 100%)' },
            { name: __('Neon Pop', 'event-o'), gradient: 'linear-gradient(135deg, #ff4ecd 0%, #7bff00 100%)' },
            { name: __('Hot Pink', 'event-o'), gradient: 'linear-gradient(135deg, #ff3d77 0%, #ff7b7b 100%)' }
        ];

        function renderPreviewChip() {
            return el('span', {
                style: {
                    display: 'inline-flex',
                    width: '20px',
                    height: '20px',
                    borderRadius: '999px',
                    marginRight: '10px',
                    border: '1px solid rgba(0,0,0,0.12)',
                    background: isGradient ? previewValue : 'none',
                    backgroundColor: isGradient ? 'transparent' : previewValue,
                    boxShadow: '0 1px 3px rgba(0,0,0,0.14)'
                }
            });
        }

        return el(PanelBody, { title: __('Highlight Badge', 'event-o'), initialOpen: false },
            infoText ? el('p', { style: { color: '#666', fontSize: '12px', marginBottom: '12px' } }, infoText) : null,
            showToggle ? el(ToggleControl, {
                label: __('Show highlight badge', 'event-o'),
                help: __('Displays a "Highlight" badge on events marked as highlights.', 'event-o'),
                checked: !!a.showHighlightBadge,
                onChange: function (v) { setAttributes({ showHighlightBadge: v }); }
            }) : null,
            (!showToggle || a.showHighlightBadge) ? el('div', { style: { marginBottom: '16px' } },
                el('label', { style: { display: 'block', marginBottom: '8px', fontWeight: '500' } }, __('Badge look', 'event-o')),
                el(Dropdown, {
                    contentClassName: 'event-o-editor-highlight-dropdown',
                    renderToggle: function (toggleProps) {
                        return el(Button, {
                            variant: 'secondary',
                            onClick: toggleProps.onToggle,
                            style: {
                                width: '100%',
                                justifyContent: 'flex-start',
                                padding: '8px 12px',
                                borderRadius: '10px'
                            }
                        },
                            renderPreviewChip(),
                            el('span', null, isGradient ? __('Gradient selected', 'event-o') : __('Color selected', 'event-o'))
                        );
                    },
                    renderContent: function () {
                        return el('div', { style: { width: '280px', padding: '12px' } },
                            el(TabPanel, {
                                className: 'event-o-highlight-tabs',
                                activeClass: 'is-active',
                                tabs: [
                                    { name: 'color', title: __('Color', 'event-o') },
                                    { name: 'gradient', title: __('Gradient', 'event-o') }
                                ]
                            }, function (tab) {
                                if (tab.name === 'color') {
                                    return el('div', null,
                                        el(ColorPicker, {
                                            color: a.highlightColor || '#e8364f',
                                            onChangeComplete: function (c) { setAttributes({ highlightColor: c.hex, highlightGradient: '' }); },
                                            disableAlpha: true
                                        })
                                    );
                                }

                                if (GradientPicker) {
                                    return el('div', null,
                                        el(GradientPicker, {
                                            value: a.highlightGradient || gradientPresets[0].gradient,
                                            gradients: gradientPresets,
                                            disableCustomGradients: false,
                                            clearable: true,
                                            onChange: function (value) { setAttributes({ highlightGradient: value || '' }); }
                                        })
                                    );
                                }

                                return el(TextControl, {
                                    label: __('Gradient override', 'event-o'),
                                    help: __('Optional CSS gradient, e.g. linear-gradient(45deg, #e8364f, #f5a623).', 'event-o'),
                                    value: a.highlightGradient || '',
                                    onChange: function (v) { setAttributes({ highlightGradient: v }); }
                                });
                            }),
                            el('div', { style: { marginTop: '10px', display: 'flex', gap: '8px', flexWrap: 'wrap' } },
                                el(Button, {
                                    isSmall: true,
                                    variant: 'tertiary',
                                    onClick: function () { setAttributes({ highlightColor: '#e8364f', highlightGradient: '' }); }
                                }, __('Reset to default', 'event-o')),
                                el(Button, {
                                    isSmall: true,
                                    variant: 'tertiary',
                                    onClick: function () { setAttributes({ highlightGradient: '' }); }
                                }, __('Remove gradient', 'event-o'))
                            )
                        );
                    }
                })
            ) : null,
            showPriority ? el(ToggleControl, {
                label: __('Show highlighted events first', 'event-o'),
                help: __('Moves active highlight events to the beginning of the block.', 'event-o'),
                checked: a.preferHighlights !== false,
                onChange: function (v) { setAttributes({ preferHighlights: v }); }
            }) : null,
            ((!showToggle || a.showHighlightBadge) || showPriority) ? el('div', {
                style: {
                    marginTop: '8px',
                    padding: '6px 12px',
                    fontFamily: 'Satisfy, cursive',
                    fontSize: '1.4rem',
                    color: isGradient ? 'transparent' : previewValue,
                    background: isGradient ? previewValue : 'none',
                    WebkitBackgroundClip: isGradient ? 'text' : 'unset',
                    WebkitTextFillColor: isGradient ? 'transparent' : 'unset'
                }
            }, 'Highlight') : null
        );
    }

    registerBlockType('event-o/event-list', {
        title: 'Event_O – Event List',
        icon: 'list-view',
        category: 'widgets',
        attributes: {
            perPage: { type: 'number', default: 10 },
            showPast: { type: 'boolean', default: false },
            showPastHeading: { type: 'boolean', default: false },
            pastEventsFirst: { type: 'boolean', default: false },
            enableLoadMore: { type: 'boolean', default: false },
            groupByMonth: { type: 'boolean', default: true },
            openFirst: { type: 'boolean', default: false },
            singleOpen: { type: 'boolean', default: false },
            categories: { type: 'string', default: '' },
            venues: { type: 'string', default: '' },
            organizers: { type: 'string', default: '' },
            showImage: { type: 'boolean', default: true },
            showVenue: { type: 'boolean', default: true },
            showOrganizer: { type: 'boolean', default: true },
            showPrice: { type: 'boolean', default: true },
            showTags: { type: 'boolean', default: false },
            showMoreLink: { type: 'boolean', default: true },
            accentColor: { type: 'string', default: '' },
            showFilters: { type: 'boolean', default: false },
            filterByCategory: { type: 'boolean', default: true },
            filterByVenue: { type: 'boolean', default: true },
            filterByOrganizer: { type: 'boolean', default: true },
            filterStyle: { type: 'string', default: 'dropdown' },
            filterCategoryColors: { type: 'boolean', default: false },
            showHighlightBadge: { type: 'boolean', default: false },
            highlightColor: { type: 'string', default: '' },
            highlightGradient: { type: 'string', default: '' },
            preferHighlights: { type: 'boolean', default: true },
            animation: { type: 'string', default: 'none' }
        },
        edit: function (props) {
            var a = props.attributes;
            var setAttributes = props.setAttributes;

            return [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: __('Query', 'event-o'), initialOpen: true },
                        el(RangeControl, {
                            label: __('Number of events', 'event-o'),
                            value: a.perPage,
                            min: 1,
                            max: 50,
                            onChange: function (v) { setAttributes({ perPage: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show past events', 'event-o'),
                            checked: a.showPast,
                            onChange: function (v) { setAttributes({ showPast: v }); }
                        }),
                        a.showPast && el(ToggleControl, {
                            label: __('Separate past events heading', 'event-o'),
                            help: __('Shows upcoming events first and adds a "(Vergangene Events)" heading before older entries.', 'event-o'),
                            checked: !!a.showPastHeading,
                            onChange: function (v) { setAttributes({ showPastHeading: v }); }
                        }),
                        a.showPast && !!a.showPastHeading && el(ToggleControl, {
                            label: __('Show past events first', 'event-o'),
                            help: __('Places the past events section before the upcoming events section.', 'event-o'),
                            checked: !!a.pastEventsFirst,
                            onChange: function (v) { setAttributes({ pastEventsFirst: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Enable load more button', 'event-o'),
                            help: __('Uses the number of events above as the initial and additional batch size.', 'event-o'),
                            checked: !!a.enableLoadMore,
                            onChange: function (v) { setAttributes({ enableLoadMore: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Group by month', 'event-o'),
                            checked: a.groupByMonth,
                            onChange: function (v) { setAttributes({ groupByMonth: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Open first item', 'event-o'),
                            checked: a.openFirst,
                            onChange: function (v) { setAttributes({ openFirst: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Allow only one open item', 'event-o'),
                            checked: a.singleOpen,
                            onChange: function (v) { setAttributes({ singleOpen: v }); }
                        })
                    ),
                    el(PanelBody, { title: __('Filters', 'event-o'), initialOpen: false },
                        el(TextControl, {
                            label: __('Categories (slugs, comma-separated)', 'event-o'),
                            value: a.categories,
                            onChange: function (v) { setAttributes({ categories: v }); }
                        }),
                        el(TextControl, {
                            label: __('Venues (slugs, comma-separated)', 'event-o'),
                            value: a.venues,
                            onChange: function (v) { setAttributes({ venues: v }); }
                        }),
                        el(TextControl, {
                            label: __('Organizers (slugs, comma-separated)', 'event-o'),
                            value: a.organizers,
                            onChange: function (v) { setAttributes({ organizers: v }); }
                        }),
                        TaxHelp(__('Example: fuehrung, ausstellung', 'event-o'))
                    ),
                    el(PanelBody, { title: __('Display', 'event-o'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show featured image', 'event-o'),
                            checked: a.showImage,
                            onChange: function (v) { setAttributes({ showImage: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show venue', 'event-o'),
                            checked: a.showVenue,
                            onChange: function (v) { setAttributes({ showVenue: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show organizer', 'event-o'),
                            checked: a.showOrganizer,
                            onChange: function (v) { setAttributes({ showOrganizer: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show price', 'event-o'),
                            checked: a.showPrice,
                            onChange: function (v) { setAttributes({ showPrice: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show tags', 'event-o'),
                            checked: a.showTags,
                            onChange: function (v) { setAttributes({ showTags: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show "More" link', 'event-o'),
                            checked: a.showMoreLink,
                            onChange: function (v) { setAttributes({ showMoreLink: v }); }
                        })
                    ),
                    el(PanelBody, { title: __('Frontend Filters', 'event-o'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show filter bar', 'event-o'),
                            help: __('Displays a filter bar for visitors to filter events.', 'event-o'),
                            checked: a.showFilters,
                            onChange: function (v) { setAttributes({ showFilters: v }); }
                        }),
                        a.showFilters && el(SelectControl, {
                            label: __('Filter style', 'event-o'),
                            value: a.filterStyle || 'dropdown',
                            options: [
                                { label: __('Dropdown', 'event-o'), value: 'dropdown' },
                                { label: __('Tabs / Pills', 'event-o'), value: 'tabs' }
                            ],
                            onChange: function (v) { setAttributes({ filterStyle: v }); }
                        }),
                        a.showFilters && (a.filterStyle || 'dropdown') === 'tabs' && a.filterByCategory && el(ToggleControl, {
                            label: __('Use category colors for active tabs', 'event-o'),
                            help: __('Selected category tabs use the assigned category color.', 'event-o'),
                            checked: !!a.filterCategoryColors,
                            onChange: function (v) { setAttributes({ filterCategoryColors: v }); }
                        }),
                        a.showFilters && el(ToggleControl, {
                            label: __('Filter by category', 'event-o'),
                            checked: a.filterByCategory,
                            onChange: function (v) { setAttributes({ filterByCategory: v }); }
                        }),
                        a.showFilters && el(ToggleControl, {
                            label: __('Filter by venue', 'event-o'),
                            checked: a.filterByVenue,
                            onChange: function (v) { setAttributes({ filterByVenue: v }); }
                        }),
                        a.showFilters && el(ToggleControl, {
                            label: __('Filter by organizer', 'event-o'),
                            checked: a.filterByOrganizer,
                            onChange: function (v) { setAttributes({ filterByOrganizer: v }); }
                        })
                    ),
                    el(PanelBody, { title: __('Colors', 'event-o'), initialOpen: false },
                        el(ColorControl, {
                            label: __('Accent Color', 'event-o'),
                            value: a.accentColor,
                            onChange: function (v) { setAttributes({ accentColor: v }); }
                        })
                    ),
                    el(PanelBody, { title: __('Animation', 'event-o'), initialOpen: false },
                        el(SelectControl, {
                            label: __('Entrance animation', 'event-o'),
                            help: __('Items animate in when scrolled into view.', 'event-o'),
                            value: a.animation || 'none',
                            options: [
                                { label: __('None', 'event-o'), value: 'none' },
                                { label: __('Fade Up', 'event-o'), value: 'fade-up' },
                                { label: __('Fade In', 'event-o'), value: 'fade-in' },
                                { label: __('Slide Left', 'event-o'), value: 'slide-left' },
                                { label: __('Scale Up', 'event-o'), value: 'scale-up' },
                                { label: __('Flip In', 'event-o'), value: 'flip-in' },
                                { label: __('Blur In', 'event-o'), value: 'blur-in' }
                            ],
                            onChange: function (v) { setAttributes({ animation: v }); }
                        })
                    ),
                    HighlightBadgePanel(a, setAttributes, { showToggle: true, showPriority: true })
                ),
                el('div', { key: 'preview', className: props.className },
                    el(ServerSideRender, {
                        block: 'event-o/event-list',
                        attributes: a
                    })
                )
            ];
        },
        save: function () {
            return null;
        }
    });

    registerBlockType('event-o/event-carousel', {
        title: 'Event_O – Event Carousel',
        icon: 'slides',
        category: 'widgets',
        attributes: {
            perPage: { type: 'number', default: 8 },
            showPast: { type: 'boolean', default: false },
            sortOrder: { type: 'string', default: 'asc' },
            categories: { type: 'string', default: '' },
            venues: { type: 'string', default: '' },
            organizers: { type: 'string', default: '' },
            slidesToShow: { type: 'number', default: 3 },
            showImage: { type: 'boolean', default: true },
            showVenue: { type: 'boolean', default: true },
            showPrice: { type: 'boolean', default: true },
            hoverExcerptWords: { type: 'number', default: 32 },
            accentColor: { type: 'string', default: '' },
            showFilters: { type: 'boolean', default: false },
            filterByCategory: { type: 'boolean', default: true },
            filterByVenue: { type: 'boolean', default: true },
            filterByOrganizer: { type: 'boolean', default: true },
            showHighlightBadge: { type: 'boolean', default: false },
            highlightColor: { type: 'string', default: '' },
            highlightGradient: { type: 'string', default: '' },
            preferHighlights: { type: 'boolean', default: true },
            autoPlay: { type: 'boolean', default: false },
            autoPlayInterval: { type: 'number', default: 5 }
        },
        edit: function (props) {
            var a = props.attributes;
            var setAttributes = props.setAttributes;

            return [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: __('Query', 'event-o'), initialOpen: true },
                        el(RangeControl, {
                            label: __('Number of events', 'event-o'),
                            value: a.perPage,
                            min: 1,
                            max: 50,
                            onChange: function (v) { setAttributes({ perPage: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show past events', 'event-o'),
                            checked: a.showPast,
                            onChange: function (v) { setAttributes({ showPast: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Sort by date descending', 'event-o'),
                            help: __('Enabled: newest first. Disabled: soonest first.', 'event-o'),
                            checked: (a.sortOrder || 'asc') === 'desc',
                            onChange: function (v) { setAttributes({ sortOrder: v ? 'desc' : 'asc' }); }
                        }),
                        el(RangeControl, {
                            label: __('Slides to show', 'event-o'),
                            value: a.slidesToShow,
                            min: 1,
                            max: 6,
                            onChange: function (v) { setAttributes({ slidesToShow: v }); }
                        })
                    ),
                    el(PanelBody, { title: __('Filters', 'event-o'), initialOpen: false },
                        el(TextControl, {
                            label: __('Categories (slugs, comma-separated)', 'event-o'),
                            value: a.categories,
                            onChange: function (v) { setAttributes({ categories: v }); }
                        }),
                        el(TextControl, {
                            label: __('Venues (slugs, comma-separated)', 'event-o'),
                            value: a.venues,
                            onChange: function (v) { setAttributes({ venues: v }); }
                        }),
                        el(TextControl, {
                            label: __('Organizers (slugs, comma-separated)', 'event-o'),
                            value: a.organizers,
                            onChange: function (v) { setAttributes({ organizers: v }); }
                        }),
                        TaxHelp(__('Example: neckarstadt, geschichtswerkstatt', 'event-o'))
                    ),
                    el(PanelBody, { title: __('Display', 'event-o'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show featured image', 'event-o'),
                            checked: a.showImage,
                            onChange: function (v) { setAttributes({ showImage: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show venue', 'event-o'),
                            checked: a.showVenue,
                            onChange: function (v) { setAttributes({ showVenue: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show price', 'event-o'),
                            checked: a.showPrice,
                            onChange: function (v) { setAttributes({ showPrice: v }); }
                        }),
                        el(RangeControl, {
                            label: __('Hover text length (words)', 'event-o'),
                            help: __('Controls how much description text is shown in the image overlay.', 'event-o'),
                            value: a.hoverExcerptWords || 32,
                            min: 10,
                            max: 80,
                            step: 2,
                            onChange: function (v) { setAttributes({ hoverExcerptWords: v || 32 }); }
                        })
                    ),
                    el(PanelBody, { title: __('Frontend Filters', 'event-o'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show filter bar', 'event-o'),
                            help: __('Displays a filter bar for visitors to filter events.', 'event-o'),
                            checked: a.showFilters,
                            onChange: function (v) { setAttributes({ showFilters: v }); }
                        }),
                        a.showFilters && el(ToggleControl, {
                            label: __('Filter by category', 'event-o'),
                            checked: a.filterByCategory,
                            onChange: function (v) { setAttributes({ filterByCategory: v }); }
                        }),
                        a.showFilters && el(ToggleControl, {
                            label: __('Filter by venue', 'event-o'),
                            checked: a.filterByVenue,
                            onChange: function (v) { setAttributes({ filterByVenue: v }); }
                        }),
                        a.showFilters && el(ToggleControl, {
                            label: __('Filter by organizer', 'event-o'),
                            checked: a.filterByOrganizer,
                            onChange: function (v) { setAttributes({ filterByOrganizer: v }); }
                        })
                    ),
                    el(PanelBody, { title: __('Colors', 'event-o'), initialOpen: false },
                        el(ColorControl, {
                            label: __('Accent Color', 'event-o'),
                            value: a.accentColor,
                            onChange: function (v) { setAttributes({ accentColor: v }); }
                        })
                    ),
                    el(PanelBody, { title: __('Autoplay', 'event-o'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Auto-scroll', 'event-o'),
                            help: __('Automatically scrolls through events.', 'event-o'),
                            checked: a.autoPlay,
                            onChange: function (v) { setAttributes({ autoPlay: v }); }
                        }),
                        a.autoPlay && el(RangeControl, {
                            label: __('Interval (seconds)', 'event-o'),
                            value: a.autoPlayInterval,
                            min: 1,
                            max: 30,
                            onChange: function (v) { setAttributes({ autoPlayInterval: v }); }
                        })
                    ),
                    HighlightBadgePanel(a, setAttributes, { showToggle: true, showPriority: true })
                ),
                el('div', { key: 'preview', className: props.className },
                    el(ServerSideRender, {
                        block: 'event-o/event-carousel',
                        attributes: a
                    })
                )
            ];
        },
        save: function () {
            return null;
        }
    });

    registerBlockType('event-o/event-grid', {
        title: 'Event_O – Event Grid',
        icon: 'grid-view',
        category: 'widgets',
        attributes: {
            perPage: { type: 'number', default: 4 },
            columns: { type: 'number', default: 4 },
            showPast: { type: 'boolean', default: false },
            sortOrder: { type: 'string', default: 'auto' },
            categories: { type: 'string', default: '' },
            venues: { type: 'string', default: '' },
            organizers: { type: 'string', default: '' },
            showImage: { type: 'boolean', default: true },
            showOrganizer: { type: 'boolean', default: true },
            showCategory: { type: 'boolean', default: true },
            showVenue: { type: 'boolean', default: false },
            showPrice: { type: 'boolean', default: true },
            hoverExcerptWords: { type: 'number', default: 32 },
            accentColor: { type: 'string', default: '' },
            showFilters: { type: 'boolean', default: false },
            filterByCategory: { type: 'boolean', default: true },
            filterByVenue: { type: 'boolean', default: true },
            filterByOrganizer: { type: 'boolean', default: true },
            showHighlightBadge: { type: 'boolean', default: false },
            highlightColor: { type: 'string', default: '' },
            highlightGradient: { type: 'string', default: '' },
            preferHighlights: { type: 'boolean', default: true }
        },
        edit: function (props) {
            var a = props.attributes;
            var setAttributes = props.setAttributes;

            return [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: __('Query', 'event-o'), initialOpen: true },
                        el(RangeControl, {
                            label: __('Number of events', 'event-o'),
                            value: a.perPage,
                            min: 1,
                            max: 12,
                            onChange: function (v) { setAttributes({ perPage: v }); }
                        }),
                        el(RangeControl, {
                            label: __('Columns (max)', 'event-o'),
                            value: a.columns,
                            min: 1,
                            max: 4,
                            onChange: function (v) { setAttributes({ columns: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show past events', 'event-o'),
                            checked: a.showPast,
                            onChange: function (v) { setAttributes({ showPast: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Sort by date descending', 'event-o'),
                            help: __('Enabled: newest first. Disabled: oldest first.', 'event-o'),
                            checked: (a.sortOrder || 'auto') === 'desc' || ((a.sortOrder || 'auto') === 'auto' && !!a.showPast),
                            onChange: function (v) { setAttributes({ sortOrder: v ? 'desc' : 'asc' }); }
                        })
                    ),
                    el(PanelBody, { title: __('Filters', 'event-o'), initialOpen: false },
                        el(TextControl, {
                            label: __('Categories (slugs, comma-separated)', 'event-o'),
                            value: a.categories,
                            onChange: function (v) { setAttributes({ categories: v }); }
                        }),
                        el(TextControl, {
                            label: __('Venues (slugs, comma-separated)', 'event-o'),
                            value: a.venues,
                            onChange: function (v) { setAttributes({ venues: v }); }
                        }),
                        el(TextControl, {
                            label: __('Organizers (slugs, comma-separated)', 'event-o'),
                            value: a.organizers,
                            onChange: function (v) { setAttributes({ organizers: v }); }
                        }),
                        TaxHelp(__('Example: fuehrung, ausstellung', 'event-o'))
                    ),
                    el(PanelBody, { title: __('Display', 'event-o'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show featured image', 'event-o'),
                            checked: a.showImage,
                            onChange: function (v) { setAttributes({ showImage: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show organizer', 'event-o'),
                            checked: a.showOrganizer,
                            onChange: function (v) { setAttributes({ showOrganizer: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show category', 'event-o'),
                            checked: a.showCategory,
                            onChange: function (v) { setAttributes({ showCategory: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show venue', 'event-o'),
                            checked: a.showVenue,
                            onChange: function (v) { setAttributes({ showVenue: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show price', 'event-o'),
                            checked: a.showPrice,
                            onChange: function (v) { setAttributes({ showPrice: v }); }
                        }),
                        el(RangeControl, {
                            label: __('Hover text length (words)', 'event-o'),
                            help: __('Controls how much description text is shown in the image overlay.', 'event-o'),
                            value: a.hoverExcerptWords || 32,
                            min: 10,
                            max: 80,
                            step: 2,
                            onChange: function (v) { setAttributes({ hoverExcerptWords: v || 32 }); }
                        })
                    ),
                    el(PanelBody, { title: __('Frontend Filters', 'event-o'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show filter bar', 'event-o'),
                            help: __('Displays a filter bar for visitors to filter events.', 'event-o'),
                            checked: a.showFilters,
                            onChange: function (v) { setAttributes({ showFilters: v }); }
                        }),
                        a.showFilters && el(ToggleControl, {
                            label: __('Filter by category', 'event-o'),
                            checked: a.filterByCategory,
                            onChange: function (v) { setAttributes({ filterByCategory: v }); }
                        }),
                        a.showFilters && el(ToggleControl, {
                            label: __('Filter by venue', 'event-o'),
                            checked: a.filterByVenue,
                            onChange: function (v) { setAttributes({ filterByVenue: v }); }
                        }),
                        a.showFilters && el(ToggleControl, {
                            label: __('Filter by organizer', 'event-o'),
                            checked: a.filterByOrganizer,
                            onChange: function (v) { setAttributes({ filterByOrganizer: v }); }
                        })
                    ),
                    el(PanelBody, { title: __('Colors', 'event-o'), initialOpen: false },
                        el(ColorControl, {
                            label: __('Accent Color', 'event-o'),
                            value: a.accentColor,
                            onChange: function (v) { setAttributes({ accentColor: v }); }
                        })
                    ),
                    HighlightBadgePanel(a, setAttributes, { showToggle: true, showPriority: true })
                ),
                el('div', { key: 'preview', className: props.className },
                    el(ServerSideRender, {
                        block: 'event-o/event-grid',
                        attributes: a
                    })
                )
            ];
        },
        save: function () {
            return null;
        }
    });

    registerBlockType('event-o/event-hero', {
        title: 'Event_O – Event Hero',
        icon: 'cover-image',
        category: 'widgets',
        supports: {
            align: ['wide', 'full']
        },
        attributes: {
            perPage: { type: 'number', default: 5 },
            showPast: { type: 'boolean', default: false },
            categories: { type: 'string', default: '' },
            venues: { type: 'string', default: '' },
            organizers: { type: 'string', default: '' },
            showDate: { type: 'boolean', default: true },
            dateVariant: { type: 'string', default: 'date' },
            showDesc: { type: 'boolean', default: true },
            descWordLimit: { type: 'number', default: 20 },
            showButton: { type: 'boolean', default: true },
            buttonStyle: { type: 'string', default: 'rounded' },
            buttonText: { type: 'string', default: '' },
            accentColor: { type: 'string', default: '' },
            heroHeight: { type: 'number', default: 520 },
            overlayColor: { type: 'string', default: 'black' },
            autoPlay: { type: 'boolean', default: true },
            autoPlayInterval: { type: 'number', default: 5 },
            showFilters: { type: 'boolean', default: false },
            filterByCategory: { type: 'boolean', default: true },
            filterByVenue: { type: 'boolean', default: true },
            filterByOrganizer: { type: 'boolean', default: true },
            filterStyle: { type: 'string', default: 'dropdown' },
            onePerCategory: { type: 'boolean', default: false },
            preferHighlights: { type: 'boolean', default: true },
            highlightColor: { type: 'string', default: '' },
            highlightGradient: { type: 'string', default: '' }
        },
        edit: function (props) {
            var a = props.attributes;
            var setAttributes = props.setAttributes;

            return [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: __('Query', 'event-o'), initialOpen: true },
                        el(RangeControl, {
                            label: __('Number of events', 'event-o'),
                            value: a.perPage,
                            min: 1,
                            max: 20,
                            onChange: function (v) { setAttributes({ perPage: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show past events', 'event-o'),
                            checked: a.showPast,
                            onChange: function (v) { setAttributes({ showPast: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Prefer highlighted events first', 'event-o'),
                            help: __('Shows active event highlights before regular events.', 'event-o'),
                            checked: a.preferHighlights !== false,
                            onChange: function (v) { setAttributes({ preferHighlights: v }); }
                        })
                    ),
                    el(PanelBody, { title: __('Filters', 'event-o'), initialOpen: false },
                        el(TextControl, {
                            label: __('Categories (slugs, comma-separated)', 'event-o'),
                            value: a.categories,
                            onChange: function (v) { setAttributes({ categories: v }); }
                        }),
                        el(TextControl, {
                            label: __('Venues (slugs, comma-separated)', 'event-o'),
                            value: a.venues,
                            onChange: function (v) { setAttributes({ venues: v }); }
                        }),
                        el(TextControl, {
                            label: __('Organizers (slugs, comma-separated)', 'event-o'),
                            value: a.organizers,
                            onChange: function (v) { setAttributes({ organizers: v }); }
                        }),
                        TaxHelp(__('Example: fuehrung, ausstellung', 'event-o'))
                    ),
                    el(PanelBody, { title: __('Frontend Filters', 'event-o'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show filter bar', 'event-o'),
                            help: __('Displays a filter bar for visitors to filter events.', 'event-o'),
                            checked: a.showFilters,
                            onChange: function (v) { setAttributes({ showFilters: v }); }
                        }),
                        a.showFilters && el(ToggleControl, {
                            label: __('Filter by category', 'event-o'),
                            checked: a.filterByCategory,
                            onChange: function (v) { setAttributes({ filterByCategory: v }); }
                        }),
                        a.showFilters && el(ToggleControl, {
                            label: __('Filter by venue', 'event-o'),
                            checked: a.filterByVenue,
                            onChange: function (v) { setAttributes({ filterByVenue: v }); }
                        }),
                        a.showFilters && el(ToggleControl, {
                            label: __('Filter by organizer', 'event-o'),
                            checked: a.filterByOrganizer,
                            onChange: function (v) { setAttributes({ filterByOrganizer: v }); }
                        })
                    ),
                    el(PanelBody, { title: __('Layout', 'event-o'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('One event per category', 'event-o'),
                            help: __('Show only the next upcoming event from each category.', 'event-o'),
                            checked: !!a.onePerCategory,
                            onChange: function (v) { setAttributes({ onePerCategory: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show date', 'event-o'),
                            checked: a.showDate,
                            onChange: function (v) { setAttributes({ showDate: v }); }
                        }),
                        a.showDate && el('div', { style: { marginBottom: '16px' } },
                            el('label', { style: { display: 'block', marginBottom: '8px', fontWeight: '500' } }, __('Date style', 'event-o')),
                            el('div', { style: { display: 'flex', gap: '8px' } },
                                el(Button, {
                                    variant: (a.dateVariant || 'date') === 'date' ? 'primary' : 'secondary',
                                    onClick: function () { setAttributes({ dateVariant: 'date' }); }
                                }, __('Date only', 'event-o')),
                                el(Button, {
                                    variant: (a.dateVariant || 'date') === 'date-time' ? 'primary' : 'secondary',
                                    onClick: function () { setAttributes({ dateVariant: 'date-time' }); }
                                }, __('Date + time', 'event-o'))
                            )
                        ),
                        el(ToggleControl, {
                            label: __('Show description', 'event-o'),
                            checked: a.showDesc,
                            onChange: function (v) { setAttributes({ showDesc: v }); }
                        }),
                        a.showDesc && el(RangeControl, {
                            label: __('Description word limit', 'event-o'),
                            help: __('Max. number of words shown in the hero description.', 'event-o'),
                            value: a.descWordLimit || 20,
                            min: 5,
                            max: 60,
                            onChange: function (v) { setAttributes({ descWordLimit: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show button', 'event-o'),
                            checked: a.showButton,
                            onChange: function (v) { setAttributes({ showButton: v }); }
                        }),
                        a.showButton && el('div', { style: { marginBottom: '16px' } },
                            el('label', { style: { display: 'block', marginBottom: '8px', fontWeight: '500' } }, __('Button style', 'event-o')),
                            el('div', { style: { display: 'flex', gap: '8px', flexWrap: 'wrap' } },
                                el(Button, {
                                    variant: (a.buttonStyle || 'rounded') === 'rounded' ? 'primary' : 'secondary',
                                    onClick: function () { setAttributes({ buttonStyle: 'rounded' }); }
                                }, __('Rounded', 'event-o')),
                                el(Button, {
                                    variant: (a.buttonStyle || 'rounded') === 'square' ? 'primary' : 'secondary',
                                    onClick: function () { setAttributes({ buttonStyle: 'square' }); }
                                }, __('Square', 'event-o')),
                                el(Button, {
                                    variant: (a.buttonStyle || 'rounded') === 'outline' ? 'primary' : 'secondary',
                                    onClick: function () { setAttributes({ buttonStyle: 'outline' }); }
                                }, __('Outline', 'event-o'))
                            )
                        ),
                        a.showButton && el(TextControl, {
                            label: __('Button text', 'event-o'),
                            help: __('Leave empty to use default: "Zu den Events"', 'event-o'),
                            value: a.buttonText || '',
                            onChange: function (v) { setAttributes({ buttonText: v }); }
                        }),
                        el(RangeControl, {
                            label: __('Height (px)', 'event-o'),
                            help: __('Minimum height of the hero area.', 'event-o'),
                            value: a.heroHeight || 520,
                            min: 520,
                            max: 720,
                            step: 10,
                            onChange: function (v) { setAttributes({ heroHeight: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Auto-play', 'event-o'),
                            help: __('Automatically cycle through slides.', 'event-o'),
                            checked: a.autoPlay !== false,
                            onChange: function (v) { setAttributes({ autoPlay: v }); }
                        }),
                        a.autoPlay !== false && el(RangeControl, {
                            label: __('Interval (seconds)', 'event-o'),
                            help: __('Time between slide changes.', 'event-o'),
                            value: a.autoPlayInterval || 5,
                            min: 2,
                            max: 15,
                            onChange: function (v) { setAttributes({ autoPlayInterval: v }); }
                        })
                    ),
                    el(PanelBody, { title: __('Colors', 'event-o'), initialOpen: false },
                        el(ColorControl, {
                            label: __('Accent Color', 'event-o'),
                            value: a.accentColor,
                            onChange: function (v) { setAttributes({ accentColor: v }); }
                        }),
                        el('div', { style: { marginBottom: '16px' } },
                            el('label', { style: { display: 'block', marginBottom: '8px', fontWeight: '500' } }, __('Overlay Gradient', 'event-o')),
                            el('div', { style: { display: 'flex', gap: '8px' } },
                                el(Button, {
                                    variant: a.overlayColor === 'black' ? 'primary' : 'secondary',
                                    onClick: function () { setAttributes({ overlayColor: 'black' }); }
                                }, __('Black', 'event-o')),
                                el(Button, {
                                    variant: a.overlayColor === 'white' ? 'primary' : 'secondary',
                                    onClick: function () { setAttributes({ overlayColor: 'white' }); }
                                }, __('White', 'event-o'))
                            )
                        )
                    ),
                    HighlightBadgePanel(a, setAttributes, {
                        showToggle: false,
                        showPriority: false,
                        infoText: __('The badge is shown when "Prefer highlighted events" is enabled above and an event is marked as highlight.', 'event-o')
                    })
                ),
                el('div', { key: 'preview', className: props.className },
                    el(ServerSideRender, {
                        block: 'event-o/event-hero',
                        attributes: a
                    })
                )
            ];
        },
        save: function () {
            return null;
        }
    });

    registerBlockType('event-o/event-program', {
        title: 'Event_O – Event Program',
        icon: 'schedule',
        category: 'widgets',
        attributes: {
            perPage: { type: 'number', default: 8 },
            showPast: { type: 'boolean', default: false },
            categories: { type: 'string', default: '' },
            venues: { type: 'string', default: '' },
            organizers: { type: 'string', default: '' },
            showImage: { type: 'boolean', default: true },
            showVenue: { type: 'boolean', default: true },
            showCategory: { type: 'boolean', default: true },
            showDescription: { type: 'boolean', default: true },
            showCalendar: { type: 'boolean', default: true },
            showShare: { type: 'boolean', default: true },
            showBands: { type: 'boolean', default: true },
            showPrice: { type: 'boolean', default: true },
            accentColor: { type: 'string', default: '' },
            animation: { type: 'string', default: 'none' },
            showFilters: { type: 'boolean', default: false },
            filterByCategory: { type: 'boolean', default: true },
            filterByVenue: { type: 'boolean', default: true },
            filterByOrganizer: { type: 'boolean', default: true },
            filterStyle: { type: 'string', default: 'dropdown' },
            filterCategoryColors: { type: 'boolean', default: false },
            showHighlightBadge: { type: 'boolean', default: false },
            highlightColor: { type: 'string', default: '' },
            highlightGradient: { type: 'string', default: '' },
            preferHighlights: { type: 'boolean', default: true }
        },
        edit: function (props) {
            var a = props.attributes;
            var setAttributes = props.setAttributes;

            return [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: __('Query', 'event-o'), initialOpen: true },
                        el(RangeControl, {
                            label: __('Events before "load more"', 'event-o'),
                            value: a.perPage,
                            min: 1,
                            max: 50,
                            onChange: function (v) { setAttributes({ perPage: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show past events', 'event-o'),
                            checked: a.showPast,
                            onChange: function (v) { setAttributes({ showPast: v }); }
                        })
                    ),
                    el(PanelBody, { title: __('Filters', 'event-o'), initialOpen: false },
                        el(TextControl, {
                            label: __('Categories (slugs, comma-separated)', 'event-o'),
                            value: a.categories,
                            onChange: function (v) { setAttributes({ categories: v }); }
                        }),
                        el(TextControl, {
                            label: __('Venues (slugs, comma-separated)', 'event-o'),
                            value: a.venues,
                            onChange: function (v) { setAttributes({ venues: v }); }
                        }),
                        el(TextControl, {
                            label: __('Organizers (slugs, comma-separated)', 'event-o'),
                            value: a.organizers,
                            onChange: function (v) { setAttributes({ organizers: v }); }
                        }),
                        TaxHelp(__('Example: konzert, lesung', 'event-o'))
                    ),
                    el(PanelBody, { title: __('Frontend Filters', 'event-o'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show filter bar', 'event-o'),
                            help: __('Displays a filter bar for visitors to filter events.', 'event-o'),
                            checked: a.showFilters,
                            onChange: function (v) { setAttributes({ showFilters: v }); }
                        }),
                        a.showFilters && el(SelectControl, {
                            label: __('Filter style', 'event-o'),
                            value: a.filterStyle || 'dropdown',
                            options: [
                                { label: __('Dropdown', 'event-o'), value: 'dropdown' },
                                { label: __('Tabs / Pills', 'event-o'), value: 'tabs' }
                            ],
                            onChange: function (v) { setAttributes({ filterStyle: v }); }
                        }),
                        a.showFilters && (a.filterStyle || 'dropdown') === 'tabs' && a.filterByCategory && el(ToggleControl, {
                            label: __('Use category colors for active tabs', 'event-o'),
                            help: __('Selected category tabs use the assigned category color.', 'event-o'),
                            checked: !!a.filterCategoryColors,
                            onChange: function (v) { setAttributes({ filterCategoryColors: v }); }
                        }),
                        a.showFilters && el(ToggleControl, {
                            label: __('Filter by category', 'event-o'),
                            checked: a.filterByCategory,
                            onChange: function (v) { setAttributes({ filterByCategory: v }); }
                        }),
                        a.showFilters && el(ToggleControl, {
                            label: __('Filter by venue', 'event-o'),
                            checked: a.filterByVenue,
                            onChange: function (v) { setAttributes({ filterByVenue: v }); }
                        }),
                        a.showFilters && el(ToggleControl, {
                            label: __('Filter by organizer', 'event-o'),
                            checked: a.filterByOrganizer,
                            onChange: function (v) { setAttributes({ filterByOrganizer: v }); }
                        })
                    ),
                    el(PanelBody, { title: __('Display', 'event-o'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show featured image', 'event-o'),
                            checked: a.showImage,
                            onChange: function (v) { setAttributes({ showImage: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show category', 'event-o'),
                            checked: a.showCategory,
                            onChange: function (v) { setAttributes({ showCategory: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show venue', 'event-o'),
                            checked: a.showVenue,
                            onChange: function (v) { setAttributes({ showVenue: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show description', 'event-o'),
                            checked: a.showDescription,
                            onChange: function (v) { setAttributes({ showDescription: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show price', 'event-o'),
                            checked: a.showPrice,
                            onChange: function (v) { setAttributes({ showPrice: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show bands / artists', 'event-o'),
                            checked: a.showBands,
                            onChange: function (v) { setAttributes({ showBands: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show share buttons', 'event-o'),
                            checked: a.showShare,
                            onChange: function (v) { setAttributes({ showShare: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show calendar save', 'event-o'),
                            checked: a.showCalendar,
                            onChange: function (v) { setAttributes({ showCalendar: v }); }
                        })
                    ),
                    el(PanelBody, { title: __('Colors', 'event-o'), initialOpen: false },
                        el(ColorControl, {
                            label: __('Accent Color', 'event-o'),
                            value: a.accentColor,
                            onChange: function (v) { setAttributes({ accentColor: v }); }
                        })
                    ),
                    el(PanelBody, { title: __('Animation', 'event-o'), initialOpen: false },
                        el(SelectControl, {
                            label: __('Entrance animation', 'event-o'),
                            help: __('Items animate in when scrolled into view.', 'event-o'),
                            value: a.animation || 'none',
                            options: [
                                { label: __('None', 'event-o'), value: 'none' },
                                { label: __('Fade Up', 'event-o'), value: 'fade-up' },
                                { label: __('Fade In', 'event-o'), value: 'fade-in' },
                                { label: __('Slide Left', 'event-o'), value: 'slide-left' },
                                { label: __('Scale Up', 'event-o'), value: 'scale-up' },
                                { label: __('Flip In', 'event-o'), value: 'flip-in' },
                                { label: __('Blur In', 'event-o'), value: 'blur-in' }
                            ],
                            onChange: function (v) { setAttributes({ animation: v }); }
                        })
                    ),
                    HighlightBadgePanel(a, setAttributes, { showToggle: true, showPriority: true })
                ),
                el('div', { key: 'preview', className: props.className },
                    el(ServerSideRender, {
                        block: 'event-o/event-program',
                        attributes: a
                    })
                )
            ];
        },
        save: function () {
            return null;
        }
    });

    registerBlockType('event-o/event-calendar', {
        title: 'Event_O – Event Calendar',
        icon: 'calendar',
        category: 'widgets',
        description: __('Displays events in an interactive monthly calendar view.', 'event-o'),
        attributes: {
            theme: { type: 'string', default: 'light' },
            desktopPopupMatrix: { type: 'string', default: '3x3' },
            accentColor: { type: 'string', default: '#4f6b3a' },
            calendarBgLight: { type: 'string', default: '#f3f5f7' },
            calendarBgDark: { type: 'string', default: '#10141a' },
            dayBgLight: { type: 'string', default: '#ffffff' },
            dayBgDark: { type: 'string', default: '#1b2330' },
            weekStartsMonday: { type: 'boolean', default: true },
            popupBlur: { type: 'boolean', default: true },
            showSubscribe: { type: 'boolean', default: true },
            categories: { type: 'string', default: '' },
            venues: { type: 'string', default: '' },
            organizers: { type: 'string', default: '' }
        },
        edit: function (props) {
            var a = props.attributes;
            var setAttributes = props.setAttributes;
            var wrapRef = useRef(null);

            useEffect(function () {
                if (!wrapRef.current) {
                    return;
                }

                var observer = new MutationObserver(function () {
                    var calWrap = wrapRef.current && wrapRef.current.querySelector('.event-o-cal-wrap');
                    if (calWrap && !calWrap.getAttribute('data-cal-inited') && window.eventOCalendarInit) {
                        window.eventOCalendarInit(calWrap);
                    }
                });

                observer.observe(wrapRef.current, { childList: true, subtree: true });
                return function () { observer.disconnect(); };
            });

            return [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: __('Theme', 'event-o'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Color theme', 'event-o'),
                            value: a.theme || 'light',
                            options: [
                                { label: __('Auto (follows system)', 'event-o'), value: 'auto' },
                                { label: __('Light', 'event-o'), value: 'light' },
                                { label: __('Dark', 'event-o'), value: 'dark' }
                            ],
                            onChange: function (v) { setAttributes({ theme: v }); }
                        }),
                        el(SelectControl, {
                            label: __('Week starts on', 'event-o'),
                            value: a.weekStartsMonday ? 'monday' : 'sunday',
                            options: [
                                { label: __('Monday', 'event-o'), value: 'monday' },
                                { label: __('Sunday', 'event-o'), value: 'sunday' }
                            ],
                            onChange: function (v) { setAttributes({ weekStartsMonday: v === 'monday' }); }
                        }),
                        el(SelectControl, {
                            label: __('Desktop popup matrix', 'event-o'),
                            value: a.desktopPopupMatrix || '3x3',
                            options: [
                                { label: __('3 x 3 (default)', 'event-o'), value: '3x3' },
                                { label: __('3 x 2', 'event-o'), value: '3x2' }
                            ],
                            onChange: function (v) { setAttributes({ desktopPopupMatrix: v || '3x3' }); }
                        }),
                        el(ToggleControl, {
                            label: __('Popup Image Blur', 'event-o'),
                            help: __('Blur the background image in the event popup.', 'event-o'),
                            checked: a.popupBlur !== false,
                            onChange: function (v) { setAttributes({ popupBlur: v }); }
                        }),
                        el(ToggleControl, {
                            label: __('Subscribe Button', 'event-o'),
                            help: __('Show a calendar subscribe button in the header.', 'event-o'),
                            checked: a.showSubscribe !== false,
                            onChange: function (v) { setAttributes({ showSubscribe: v }); }
                        })
                    ),
                    el(PanelBody, { title: __('Filters', 'event-o'), initialOpen: false },
                        el(TextControl, {
                            label: __('Categories (slugs, comma-separated)', 'event-o'),
                            value: a.categories,
                            onChange: function (v) { setAttributes({ categories: v }); }
                        }),
                        el(TextControl, {
                            label: __('Venues (slugs, comma-separated)', 'event-o'),
                            value: a.venues,
                            onChange: function (v) { setAttributes({ venues: v }); }
                        }),
                        el(TextControl, {
                            label: __('Organizers (slugs, comma-separated)', 'event-o'),
                            value: a.organizers,
                            onChange: function (v) { setAttributes({ organizers: v }); }
                        }),
                        TaxHelp(__('Example: fuehrung, ausstellung', 'event-o'))
                    ),
                    el(PanelBody, { title: __('Colors', 'event-o'), initialOpen: false },
                        el(ColorControl, {
                            label: __('Accent Color', 'event-o'),
                            value: a.accentColor,
                            onChange: function (v) { setAttributes({ accentColor: v }); }
                        }),
                        el(ColorControl, {
                            label: __('Calendar Background (Light)', 'event-o'),
                            value: a.calendarBgLight,
                            onChange: function (v) { setAttributes({ calendarBgLight: v || '#f3f5f7' }); }
                        }),
                        el(ColorControl, {
                            label: __('Calendar Background (Dark)', 'event-o'),
                            value: a.calendarBgDark,
                            onChange: function (v) { setAttributes({ calendarBgDark: v || '#10141a' }); }
                        }),
                        el(ColorControl, {
                            label: __('Day Cell Color (Light)', 'event-o'),
                            value: a.dayBgLight,
                            onChange: function (v) { setAttributes({ dayBgLight: v || '#ffffff' }); }
                        }),
                        el(ColorControl, {
                            label: __('Day Cell Color (Dark)', 'event-o'),
                            value: a.dayBgDark,
                            onChange: function (v) { setAttributes({ dayBgDark: v || '#1b2330' }); }
                        })
                    )
                ),
                el('div', { key: 'preview', className: props.className, ref: wrapRef },
                    el(ServerSideRender, {
                        block: 'event-o/event-calendar',
                        attributes: a
                    })
                )
            ];
        },
        save: function () {
            return null;
        }
    });
})(window.wp);
