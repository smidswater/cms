<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\queue;

use Craft;
use craft\log\FileTarget;
use yii\queue\ErrorEvent;
use yii\queue\ExecEvent;

/**
 * Queue Log Behavior
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class QueueLogBehaviour extends VerboseBehavior
{
    /**
     * @var float timestamp
     */
    private $_jobStartedAt;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Queue::EVENT_BEFORE_EXEC => 'beforeExec',
            Queue::EVENT_AFTER_EXEC  => 'afterExec',
            Queue::EVENT_AFTER_ERROR => 'afterError',
        ];
    }


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $logDispatcher = \Craft::$app->getLog();

        foreach ($logDispatcher->targets as $target) {

            // Don't log global vars
            $target->logVars = [];

            // Set log target to queue.log
            if ($target instanceof FileTarget) {
                $target->logFile = \Craft::getAlias('@storage/logs/queue.log');
            }

            // Prevent verbose system logs
            if (!\Craft::$app->getConfig()->getGeneral()->devMode) {
                $target->except = ['yii\*'];
                $target->setLevels(['info', 'warning', 'error']);
            }
        }
    }


    /**
     * @param ExecEvent $event
     */
    public function beforeExec(ExecEvent $event)
    {
        $this->_jobStartedAt = microtime(true);

        Craft::info(sprintf(
            "%s - Started",
            parent::jobTitle($event)
        ));
    }

    /**
     * @param ExecEvent $event
     */
    public function afterExec(ExecEvent $event)
    {
        $duration = $this->getDurationFormatted();

        Craft::info(sprintf(
            "%s - Done (%s)",
            parent::jobTitle($event),
            $duration
        ));
    }

    /**
     * @param ErrorEvent $event
     */
    public function afterError(ErrorEvent $event)
    {
        $duration = $this->getDurationFormatted();
        $error    = $event->error->getMessage();

        Craft::error(sprintf(
            "%s - Error (%s): %s",
            parent::jobTitle($event),
            $duration,
            $error
        ));
    }

    /**
     * Job execution duration in seconds
     *
     * @return string
     */
    protected function getDurationFormatted(): string
    {
        return number_format(round(microtime(true) - $this->_jobStartedAt, 3), 3) . ' s';
    }
}
