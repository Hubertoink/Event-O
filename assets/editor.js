(function (wp) {
    var __ = wp.i18n.__;
    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var useRef = wp.element.useRef;
    var useEffect = wp.element.useEffect;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var ToggleControl = wp.components.ToggleControl;
    var RangeControl = wp.components.RangeControl;
    var TextControl = wp.components.TextControl;
    var ColorPicker = wp.components.ColorPicker;
    var Button = wp.components.Button;
    var SelectControl = wp.components.SelectControl;
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

    registerBlockType('event-o/event-list', {
        title: 'Event_O – Event List',
        icon: 'list-view',
        category: 'widgets',
        attributes: {
            perPage: { type: 'number', default: 10 },
            showPast: { type: 'boolean', default: false },
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
            showMoreLink: { type: 'boolean', default: true },
            accentColor: { type: 'string', default: '' },
            showFilters: { type: 'boolean', default: false },
            filterByCategory: { type: 'boolean', default: true },
            filterByVenue: { type: 'boolean', default: true },
            filterByOrganizer: { type: 'boolean', default: true },
            filterStyle: { type: 'string', default: 'dropdown' },
            filterCategoryColors: { type: 'boolean', default: false },
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
                        }),
                        a.showFilters && a.filterByCategory && el(ToggleControl, {
                            label: __('Category colors in filter', 'event-o'),
                            help: __('Show category filter buttons in their assigned colors.', 'event-o'),
                            checked: a.filterCategoryColors,
                            onChange: function (v) { setAttributes({ filterCategoryColors: v }); }
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
                    )
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
            categories: { type: 'string', default: '' },
            venues: { type: 'string', default: '' },
            organizers: { type: 'string', default: '' },
            slidesToShow: { type: 'number', default: 3 },
            showImage: { type: 'boolean', default: true },
            showVenue: { type: 'boolean', default: true },
            showPrice: { type: 'boolean', default: true },
            accentColor: { type: 'string', default: '' },
            showFilters: { type: 'boolean', default: false },
            filterByCategory: { type: 'boolean', default: true },
            filterByVenue: { type: 'boolean', default: true },
            filterByOrganizer: { type: 'boolean', default: true }
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
                    )
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
            accentColor: { type: 'string', default: '' },
            showFilters: { type: 'boolean', default: false },
            filterByCategory: { type: 'boolean', default: true },
            filterByVenue: { type: 'boolean', default: true },
            filterByOrganizer: { type: 'boolean', default: true }
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
                    )
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
            showButton: { type: 'boolean', default: true },
            buttonStyle: { type: 'string', default: 'rounded' },
            buttonText: { type: 'string', default: '' },
            accentColor: { type: 'string', default: '' },
            heroHeight: { type: 'number', default: 520 },
            overlayColor: { type: 'string', default: 'black' },
            topGradient: { type: 'string', default: 'none' },
            textAlign: { type: 'string', default: 'left' },
            autoPlay: { type: 'boolean', default: true },
            autoPlayInterval: { type: 'number', default: 5 },
            transitionSpeed: { type: 'string', default: 'medium' },
            showFilters: { type: 'boolean', default: false },
            filterByCategory: { type: 'boolean', default: true },
            filterByVenue: { type: 'boolean', default: true },
            filterByOrganizer: { type: 'boolean', default: true },
            onePerCategory: { type: 'boolean', default: false }
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
                            min: 3,
                            max: 10,
                            onChange: function (v) { setAttributes({ autoPlayInterval: v }); }
                        }),
                        a.autoPlay !== false && el(SelectControl, {
                            label: __('Transition speed', 'event-o'),
                            help: __('Speed of the slide transition animation.', 'event-o'),
                            value: a.transitionSpeed || 'medium',
                            options: [
                                { label: __('Fast', 'event-o'), value: 'fast' },
                                { label: __('Medium', 'event-o'), value: 'medium' },
                                { label: __('Slow', 'event-o'), value: 'slow' }
                            ],
                            onChange: function (v) { setAttributes({ transitionSpeed: v }); }
                        }),
                        el('div', { style: { marginBottom: '16px' } },
                            el('label', { style: { display: 'block', marginBottom: '8px', fontWeight: '500' } }, __('Text alignment', 'event-o')),
                            el('div', { style: { display: 'flex', gap: '8px' } },
                                el(Button, {
                                    variant: (a.textAlign || 'left') === 'left' ? 'primary' : 'secondary',
                                    onClick: function () { setAttributes({ textAlign: 'left' }); }
                                }, __('Left', 'event-o')),
                                el(Button, {
                                    variant: a.textAlign === 'left-center' ? 'primary' : 'secondary',
                                    onClick: function () { setAttributes({ textAlign: 'left-center' }); }
                                }, __('Left Center', 'event-o'))
                            )
                        )
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
                        ),
                        el('div', { style: { marginBottom: '16px' } },
                            el('label', { style: { display: 'block', marginBottom: '8px', fontWeight: '500' } }, __('Top Gradient (Menu)', 'event-o')),
                            el('p', { style: { fontSize: '12px', color: '#757575', marginTop: 0 } }, __('Adds a gradient at the top of the image for better menu readability.', 'event-o')),
                            el('div', { style: { display: 'flex', gap: '8px' } },
                                el(Button, {
                                    variant: (a.topGradient || 'none') === 'none' ? 'primary' : 'secondary',
                                    onClick: function () { setAttributes({ topGradient: 'none' }); }
                                }, __('None', 'event-o')),
                                el(Button, {
                                    variant: a.topGradient === 'black' ? 'primary' : 'secondary',
                                    onClick: function () { setAttributes({ topGradient: 'black' }); }
                                }, __('Black', 'event-o')),
                                el(Button, {
                                    variant: a.topGradient === 'white' ? 'primary' : 'secondary',
                                    onClick: function () { setAttributes({ topGradient: 'white' }); }
                                }, __('White', 'event-o'))
                            )
                        )
                    )
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
            filterCategoryColors: { type: 'boolean', default: false }
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
                        }),
                        a.showFilters && a.filterByCategory && el(ToggleControl, {
                            label: __('Category colors in filter', 'event-o'),
                            help: __('Show category filter buttons in their assigned colors.', 'event-o'),
                            checked: a.filterCategoryColors,
                            onChange: function (v) { setAttributes({ filterCategoryColors: v }); }
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
                    )
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
                if (!wrapRef.current) return;
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
