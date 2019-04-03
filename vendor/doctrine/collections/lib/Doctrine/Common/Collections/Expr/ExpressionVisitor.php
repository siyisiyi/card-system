<?php
 namespace Doctrine\Common\Collections\Expr; abstract class ExpressionVisitor { abstract public function walkComparison(Comparison $comparison); abstract public function walkValue(Value $value); abstract public function walkCompositeExpression(CompositeExpression $expr); public function dispatch(Expression $expr) { switch (true) { case ($expr instanceof Comparison): return $this->walkComparison($expr); case ($expr instanceof Value): return $this->walkValue($expr); case ($expr instanceof CompositeExpression): return $this->walkCompositeExpression($expr); default: throw new \RuntimeException("Unknown Expression " . get_class($expr)); } } } 