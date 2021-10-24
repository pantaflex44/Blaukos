<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* 404.html */
class __TwigTemplate_7d27d3ddca4fbf0aa685eb1b8a7f423b5a9572a2ae570955ec57e84b07f341b6 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'body' => [$this, 'block_body'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 2
        return "base.html";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        $context["title"] = _("404 - Page introuvable!");
        // line 2
        $this->parent = $this->loadTemplate("base.html", "404.html", 2);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 4
    public function block_body($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 5
        echo "    <h1>404</h1>
";
    }

    public function getTemplateName()
    {
        return "404.html";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  53 => 5,  49 => 4,  44 => 2,  42 => 1,  35 => 2,);
    }

    public function getSourceContext()
    {
        return new Source("{% set title = \"404 - Page introuvable!\"|_ %}
{% extends 'base.html' %}

{% block body %}
    <h1>404</h1>
{% endblock body %}", "404.html", "/home/christophe/OneDrive/Documents/Projets/Php/KuntoManager/core/views/404.html");
    }
}
