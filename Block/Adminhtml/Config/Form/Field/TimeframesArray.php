<?php
/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..  ..:  :  ....::
 *   ::::  :::  :  :   ::
 *   ::::  :::  :  ''' ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@totalinternetgroup.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
namespace TIG\RoutiGo\Block\Adminhtml\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\View\Element\BlockInterface;

class TimeframesArray extends AbstractFieldArray
{
    const ROUTIGO_TIMEFRAME_DAY           = 'timeframe_day';
    const ROUTIGO_TIMEFRAME_EARLIEST_TIME = 'timeframe_earliest_time';
    const ROUTIGO_TIMEFRAME_LATEST_TIME   = 'timeframe_latest_time';
    const ROUTIGO_TIMEFRAME_SORT_ORDER    = 'timeframe_sort_order';
    const ROUTIGO_TIMEFRAME_FEE           = 'timeframe_fee';

    /** @var array $_columns */
    protected $_columns = [];

    /** @var bool $_addAfter */
    protected $_addAfter = true;

    /** @var BlockInterface $timeframeDaysBlock */
    private $timeframeDaysBlock;

    protected function _construct()
    {
        $this->_addButtonLabel = __('Add Timeframe');
        parent::_construct();
    }

    /**
     * Prepare to render
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->timeframeDaysBlock = $this->getLayout()->createBlock(
            \TIG\RoutiGo\Block\Adminhtml\Config\Form\Field\TimeframeDays::class,
            '',
            ['data' => ['is_render_to_js_template' => true]]
        );

        $this->addColumn(self::ROUTIGO_TIMEFRAME_DAY, [
            'label'    => __('Weekday'),
            'renderer' => $this->timeframeDaysBlock
        ]);
        $this->addColumn(self::ROUTIGO_TIMEFRAME_EARLIEST_TIME, [
            'label'    => __('Earliest time'),
            'style'    => 'width: 110px',
            'class'    => 'routigo__starttimepicker',
            'renderer' => $this->getLayout()->createblock(
                TimePicker::class,
                '',
                ['data' => ['is_render_to_js_template']]
            )
        ]);
        $this->addColumn(self::ROUTIGO_TIMEFRAME_LATEST_TIME, [
            'label'    => __('Latest time'),
            'style'    => 'width: 110px',
            'class'    => 'routigo__endtimepicker',
            'renderer' => $this->getLayout()->createblock(
                TimePicker::class,
                '',
                ['data' => ['is_render_to_js_template']]
            )
        ]);
        $this->addColumn(self::ROUTIGO_TIMEFRAME_FEE, [
            'label' => __('Fee'),
            'style' => 'width: 80px'
        ]);
        $this->addColumn(self::ROUTIGO_TIMEFRAME_SORT_ORDER, [
            'label' => __('Sort Order'),
            'style' => 'width: 80px'
        ]);

        $this->_addAfter = false;
    }

    /**
     * @param \Magento\Framework\DataObject $row
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    // @codingStandardsIgnoreLine
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $day    = $row->getTimeframeDay();
        $options = [];

        if ($day) {
            $options['option_' . $this->timeframeDaysBlock->calcOptionHash($day)] = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }
}
