<?php

namespace FluentCrm\Framework\Foundation;

trait FoundationTrait
{
    public function env()
    {
        return $this->config->get('app.env');
    }

    public function hook($prefix, $hook)
    {
        return $prefix . $hook;
    }

    public function parseRestHandler($handler)
    {
        if (!$handler) return;

        if ($this->hasNamespace($handler)) {
            return $handler;
        }

        if (is_string($handler)) {
            $handler = $this->controllerNamespace . '\\' . $handler;
        } else if (is_array($handler)) {
            list($class, $method) = $handler;
            if (is_string($class)) {
                $handler = $this->controllerNamespace . '\\' . $class . '::' . $method;
            }
        }

        return $handler;
    }

    public function parsePolicyHandler($handler)
    {
        if (!$handler) return;

        if (is_string($handler)) {
            if ($this->hasNamespace($handler)) {
                $handler = $handler;
            } else {
                $handler = $this->policyNamespace . '\\' . $handler;
            }

            if ($this->isCallableWithAtSign($handler)) {
                list($class, $method) = explode('@', $handler);
                if (!method_exists($class, $method)) {
                    $method = 'verifyRequest';
                    if (!method_exists($class, $method)) {
                        $method = '__returnTrue';
                    }
                }
                $instance = $this->make($class);
                $handler = [$instance, $method];
            }

        } else if (is_array($handler)) {
            list($class, $method) = $handler;

            if (is_string($class)) {
                if ($this->hasNamespace($handler)) {
                    $handler = $class . '::' . $method;
                } else {
                    $handler = $this->policyNamespace . '\\' . $class . '::' . $method;
                }
            }
        }

        return $handler;
    }

    public function addAction($action, $handler, $priority = 10, $numOfArgs = 1)
    {
        return add_action(
            $action,
            $this->parseHookHandler($handler),
            $priority,
            $numOfArgs
        );
    }

    public function addCustomAction($action, $handler, $priority = 10, $numOfArgs = 1)
    {
        $prefix = $this->config->get('app.hook_prefix');

        return $this->addAction(
            $this->hook($prefix, $action), $handler, $priority, $numOfArgs
        );
    }

    public function doAction()
    {
        return call_user_func_array('do_action', func_get_args());
    }

    public function doCustomAction()
    {
        $args = func_get_args();

        $prefix = $this->config->get('app.hook_prefix');

        $args[0] = $this->hook($prefix, $args[0]);

        return call_user_func_array('do_action', $args);
    }

    public function addFilter($action, $handler, $priority = 10, $numOfArgs = 1)
    {
        return add_filter(
            $action,
            $this->parseHookHandler($handler),
            $priority,
            $numOfArgs
        );
    }

    public function addCustomFilter($action, $handler, $priority = 10, $numOfArgs = 1)
    {
        $prefix = $this->config->get('app.hook_prefix');

        return $this->addFilter(
            $this->hook($prefix, $action), $handler, $priority, $numOfArgs
        );
    }

    public function applyFilters()
    {
        return call_user_func_array('apply_filters', func_get_args());
    }

    public function applyCustomFilters()
    {
        $args = func_get_args();
        $prefix = $this->config->get('app.hook_prefix');
        $args[0] = $this->hook($prefix, $args[0]);

        return call_user_func_array('apply_filters', $args);
    }

    public function parseHookHandler($handler)
    {
        if (is_string($handler)) {
            list($class, $method) = preg_split('/::|@/', $handler);

            if ($this->hasNamespace($handler)) {
                $class = $this->make($class);
            } else {
                $class = $this->make($this->handlerNamespace . '\\' . $class);
            }

            return [$class, $method];

        } else if (is_array($handler)) {
            list($class, $method) = $handler;
            if (is_string($class)) {
                $class = $this->make($this->handlerNamespace . '\\' . $class);
            }
            return [$class, $method];
        }

        return $handler;
    }

    public function hasNamespace($handler)
    {
        if ($handler instanceof \Closure) {
            return false;
        };

        $parts = explode('\\', $handler);
        return count($parts) > 1;
    }
}
