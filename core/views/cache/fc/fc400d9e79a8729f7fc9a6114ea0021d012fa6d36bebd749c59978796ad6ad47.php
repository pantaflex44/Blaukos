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

/* 404.twig */
class __TwigTemplate_a2e54efac9be8f93375000ad63e6c72a75f564835265816e67cba3b8749425ce extends Template
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
        return "base.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        $context["title"] = _("Oups!");
        // line 2
        $this->parent = $this->loadTemplate("base.twig", "404.twig", 2);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 4
    public function block_body($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 5
        echo "    <div class=\"container-fluid\">
        <div class=\"row vh-100 gap-3\">
            <div class=\"col-12 text-center align-self-end\">
                <p class=\"display-1 text-black-50 fw-bold my-0\">
                    404
                </p>
                <p class=\"small text-muted my-0\">
                    ";
        // line 12
        echo twig_escape_filter($this->env, _("page introuvable"), "html", null, true);
        echo "
                </p>
            </div>
            <div class=\"col-12 text-center align-self-start\">
                <p class=\"display-6 my-0 text-danger\">
                    ";
        // line 17
        echo twig_escape_filter($this->env, _("Mais où vas-tu?"), "html", null, true);
        echo "
                </p>
            </div>
            <div class=\"col-12 text-nowrap text-center align-self-start\">
                <a href=\"/\" class=\"btn btn-outline-secondary\">";
        // line 21
        echo twig_escape_filter($this->env, _("retourner à l'accueil"), "html", null, true);
        echo "</a>
            </div>
        </div>
    </div>
";
    }

    public function getTemplateName()
    {
        return "404.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  77 => 21,  70 => 17,  62 => 12,  53 => 5,  49 => 4,  44 => 2,  42 => 1,  35 => 2,);
    }

    public function getSourceContext()
    {
        return new Source("{% set title = \"Oups!\"|_ %}
{% extends 'base.twig' %}

{% block body %}
    <div class=\"container-fluid\">
        <div class=\"row vh-100 gap-3\">
            <div class=\"col-12 text-center align-self-end\">
                <p class=\"display-1 text-black-50 fw-bold my-0\">
                    404
                </p>
                <p class=\"small text-muted my-0\">
                    {{ \"page introuvable\"|_ }}
                </p>
            </div>
            <div class=\"col-12 text-center align-self-start\">
                <p class=\"display-6 my-0 text-danger\">
                    {{ \"Mais où vas-tu?\"|_ }}
                </p>
            </div>
            <div class=\"col-12 text-nowrap text-center align-self-start\">
                <a href=\"/\" class=\"btn btn-outline-secondary\">{{ \"retourner à l'accueil\"|_ }}</a>
            </div>
        </div>
    </div>
{% endblock body %}", "404.twig", "/home/christophe/OneDrive/Documents/Projets/Php/KuntoManager/core/views/404.twig");
    }
}
