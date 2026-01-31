(function (wp) {
    var __ = wp.i18n.__;
    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var ToggleControl = wp.components.ToggleControl;
    var RangeControl = wp.components.RangeControl;
    var TextControl = wp.components.TextControl;
    var ColorPicker = wp.components.ColorPicker;
    var Button = wp.components.Button;
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
            categories: { type: 'string', default: '' },
            venues: { type: 'string', default: '' },
            organizers: { type: 'string', default: '' },
            showImage: { type: 'boolean', default: true },
            showVenue: { type: 'boolean', default: true },
            showOrganizer: { type: 'boolean', default: true },
            showPrice: { type: 'boolean', default: true },
            showMoreLink: { type: 'boolean', default: true },
            accentColor: { type: 'string', default: '' }
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
            accentColor: { type: 'string', default: '' }
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
            categories: { type: 'string', default: '' },
            venues: { type: 'string', default: '' },
            organizers: { type: 'string', default: '' },
            showImage: { type: 'boolean', default: true },
            showOrganizer: { type: 'boolean', default: true },
            showCategory: { type: 'boolean', default: true },
            showVenue: { type: 'boolean', default: false },
            accentColor: { type: 'string', default: '' }
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
})(window.wp);
