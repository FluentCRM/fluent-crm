<?php

namespace FluentCrm\Includes\Rest;

class Group
{
    protected $app = null;

    protected $prefix = null;

    protected $options = null;

    protected $callback = null;

    protected $policyHandler = null;

    public function __construct($app, $options, $callback)
    {
        $this->app = $app;
        $this->options = $options;
        $this->callback = $callback;
    }

    public function prefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function withPolicy($handler)
    {
        $this->policyHandler = $handler;

        return $this;
    }

    public function register()
    {
        $this->options = array_merge([
            'prefix' => $this->prefix,
            'policy' => $this->policyHandler
        ], $this->options);

        call_user_func($this->callback, $this->app, $this->options);
    }
}
