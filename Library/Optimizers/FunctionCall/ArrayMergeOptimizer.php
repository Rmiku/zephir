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
 * ArrayMergeOptimizer
 *
 * Optimizes calls to 'array_merge' using internal function
 */
class ArrayMergeOptimizer extends OptimizerAbstract
{
    /**
     * @param array $expression
     * @param Call $call
     * @param CompilationContext $context
     * @return bool|CompiledExpression|mixed
     * @throws CompilerException
     */
    public function optimize(array $expression, Call $call, CompilationContext $context)
    {
        if (!isset($expression['parameters'])) {
            return false;
        }

        if (count($expression['parameters']) != 2) {
            return false;
        }

        /**
         * Process the expected symbol to be returned
         */
        $call->processExpectedReturn($context);

        $symbolVariable = $call->getSymbolVariable(true, $context);
        if (!$symbolVariable->isVariable()) {
            throw new CompilerException("Returned values by functions can only be assigned to variant variables", $expression);
        }

        $context->headersManager->add('kernel/array');

        $symbolVariable->setDynamicTypes('array');

        $resolvedParams = $call->getReadOnlyResolvedParams($expression['parameters'], $context, $expression);
        if ($call->mustInitSymbolVariable()) {
            $symbolVariable->initVariant($context);
        }
        $symbol = $context->backend->getVariableCode($symbolVariable);
        $resolveParam = !$context->backend->isZE3() ? function ($str) {
            return '&(' . $str . ')';
        } : function ($str) {
            return $str;
        };
        $context->codePrinter->output('zephir_fast_array_merge(' . $symbol . ', ' . $resolveParam($resolvedParams[0]) . ', ' . $resolveParam($resolvedParams[1]) . ' TSRMLS_CC);');
        return new CompiledExpression('variable', $symbolVariable->getRealName(), $expression);
    }
}
