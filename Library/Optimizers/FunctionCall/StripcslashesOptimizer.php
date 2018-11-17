<?php

/**
 * This file is part of the Zephir.
 *
 * (c) Zephir Team <team@zephir-lang.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zephir\Optimizers\FunctionCall;

use Zephir\Call;
use Zephir\CompilationContext;
use Zephir\Exception\CompilerException;
use Zephir\CompiledExpression;
use Zephir\Optimizers\OptimizerAbstract;

/**
 * StripcslashesOptimizer
 *
 * Optimizes calls to 'stripcslashes' using internal function
 */
class StripcslashesOptimizer extends OptimizerAbstract
{
    /**
     * @param array $expression
     * @param Call $call
     * @param CompilationContext $context
     * @return bool|CompiledExpression|mixed
     * @throws \Zephir\Exception\CompilerException
     */
    public function optimize(array $expression, Call $call, CompilationContext $context)
    {
        if (!isset($expression['parameters'])) {
            return false;
        }

        if (count($expression['parameters']) > 1) {
            return false;
        }

        /**
         * Process the expected symbol to be returned
         */
        $call->processExpectedReturn($context);

        $symbolVariable = $call->getSymbolVariable();
        if ($symbolVariable->isNotVariableAndString()) {
            throw new CompilerException("Returned values by functions can only be assigned to variant variables", $expression);
        }

        $context->headersManager->add('kernel/string');

        $symbolVariable->setDynamicTypes('string');

        $resolvedParams = $call->getReadOnlyResolvedParams($expression['parameters'], $context, $expression);

        if ($call->mustInitSymbolVariable()) {
            $symbolVariable->initVariant($context);
        }

        $symbol = $context->backend->getVariableCode($symbolVariable);
        if ($context->backend->getName() == 'ZendEngine2') {
            $context->codePrinter->output('zephir_stripcslashes(' . $symbol . ', ' . $resolvedParams[0] . ' TSRMLS_CC);');
        } else {
            $context->codePrinter->output('zephir_stripcslashes(' . $symbol . ', ' . $resolvedParams[0] . ');');
        }

        return new CompiledExpression('variable', $symbolVariable->getRealName(), $expression);
    }
}
