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

/* base.html */
class __TwigTemplate_8ff804298244db9c1f2378ae6c9a77871edb659c7cab94a6478ed353523abc73 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'body' => [$this, 'block_body'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<!DOCTYPE html>
<html lang=\"fr\" dir=\"ltr\">
    <head>
        <meta charset=\"UTF-8\">
        <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        
        <title>";
        // line 8
        echo twig_escape_filter($this->env, (($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4 = ($context["env"] ?? null)) && is_array($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4) || $__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4 instanceof ArrayAccess ? ($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4["APP_NAME"] ?? null) : null), "html", null, true);
        echo " - ";
        echo twig_escape_filter($this->env, ($context["title"] ?? null), "html", null, true);
        echo "</title>
        <meta name=\"description\" content=\"";
        // line 9
        echo twig_escape_filter($this->env, (($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 = ($context["env"] ?? null)) && is_array($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144) || $__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 instanceof ArrayAccess ? ($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144["APP_DESCRIPTION"] ?? null) : null), "html", null, true);
        echo "\">
        <meta name=\"author\" content=\"";
        // line 10
        echo twig_escape_filter($this->env, (($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b = ($context["env"] ?? null)) && is_array($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b) || $__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b instanceof ArrayAccess ? ($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b["APP_AUTHOR_NAME"] ?? null) : null), "html", null, true);
        echo " <";
        echo twig_escape_filter($this->env, (($__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 = ($context["env"] ?? null)) && is_array($__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002) || $__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 instanceof ArrayAccess ? ($__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002["APP_AUTHOR_EMAIL"] ?? null) : null), "html", null, true);
        echo ">\">
        <meta name=\"copyright\" content=\"";
        // line 11
        echo twig_escape_filter($this->env, (($__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4 = ($context["env"] ?? null)) && is_array($__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4) || $__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4 instanceof ArrayAccess ? ($__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4["APP_COPYRIGHT"] ?? null) : null), "html", null, true);
        echo "\">
        
        <meta name=\"generator\" content=\"";
        // line 13
        echo twig_escape_filter($this->env, (($__internal_01476f8db28655ee4ee02ea2d17dd5a92599be76304f08cd8bc0e05aced30666 = ($context["env"] ?? null)) && is_array($__internal_01476f8db28655ee4ee02ea2d17dd5a92599be76304f08cd8bc0e05aced30666) || $__internal_01476f8db28655ee4ee02ea2d17dd5a92599be76304f08cd8bc0e05aced30666 instanceof ArrayAccess ? ($__internal_01476f8db28655ee4ee02ea2d17dd5a92599be76304f08cd8bc0e05aced30666["APP_NAME"] ?? null) : null), "html", null, true);
        echo "\">
        <meta name=\"robots\" content=\"none\">

        <link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"/images/favicons/apple-touch-icon.png\">
        <link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"/images/favicons/favicon-32x32.png\">
        <link rel=\"icon\" type=\"image/png\" sizes=\"194x194\" href=\"/images/favicons/favicon-194x194.png\">
        <link rel=\"icon\" type=\"image/png\" sizes=\"192x192\" href=\"/images/favicons/android-chrome-192x192.png\">
        <link rel=\"icon\" type=\"image/png\" sizes=\"16x16\" href=\"/images/favicons/favicon-16x16.png\">
        <link rel=\"manifest\" href=\"/images/favicons/site.webmanifest\">
        <link rel=\"mask-icon\" href=\"/images/favicons/safari-pinned-tab.svg\" color=\"#5bbad5\">
        <meta name=\"msapplication-TileColor\" content=\"#2d89ef\">
        <meta name=\"msapplication-TileImage\" content=\"/images/favicons/mstile-144x144.png\">
        <meta name=\"theme-color\" content=\"#ffffff\">

        <link rel=\"stylesheet\" href=\"https://pro.fontawesome.com/releases/v5.10.0/css/all.css\" integrity=\"sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p\" crossorigin=\"anonymous\"/>
        <link rel=\"stylesheet\" href=\"base.css\">
        
    </head>
    <body>
        ";
        // line 32
        $this->displayBlock('body', $context, $blocks);
        // line 35
        echo "    </body>
</html>";
    }

    // line 32
    public function block_body($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 33
        echo "
        ";
    }

    public function getTemplateName()
    {
        return "base.html";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  101 => 33,  97 => 32,  92 => 35,  90 => 32,  68 => 13,  63 => 11,  57 => 10,  53 => 9,  47 => 8,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<!DOCTYPE html>
<html lang=\"fr\" dir=\"ltr\">
    <head>
        <meta charset=\"UTF-8\">
        <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        
        <title>{{ env['APP_NAME'] }} - {{ title }}</title>
        <meta name=\"description\" content=\"{{ env['APP_DESCRIPTION'] }}\">
        <meta name=\"author\" content=\"{{ env['APP_AUTHOR_NAME'] }} <{{ env['APP_AUTHOR_EMAIL'] }}>\">
        <meta name=\"copyright\" content=\"{{ env['APP_COPYRIGHT'] }}\">
        
        <meta name=\"generator\" content=\"{{ env['APP_NAME'] }}\">
        <meta name=\"robots\" content=\"none\">

        <link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"/images/favicons/apple-touch-icon.png\">
        <link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"/images/favicons/favicon-32x32.png\">
        <link rel=\"icon\" type=\"image/png\" sizes=\"194x194\" href=\"/images/favicons/favicon-194x194.png\">
        <link rel=\"icon\" type=\"image/png\" sizes=\"192x192\" href=\"/images/favicons/android-chrome-192x192.png\">
        <link rel=\"icon\" type=\"image/png\" sizes=\"16x16\" href=\"/images/favicons/favicon-16x16.png\">
        <link rel=\"manifest\" href=\"/images/favicons/site.webmanifest\">
        <link rel=\"mask-icon\" href=\"/images/favicons/safari-pinned-tab.svg\" color=\"#5bbad5\">
        <meta name=\"msapplication-TileColor\" content=\"#2d89ef\">
        <meta name=\"msapplication-TileImage\" content=\"/images/favicons/mstile-144x144.png\">
        <meta name=\"theme-color\" content=\"#ffffff\">

        <link rel=\"stylesheet\" href=\"https://pro.fontawesome.com/releases/v5.10.0/css/all.css\" integrity=\"sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p\" crossorigin=\"anonymous\"/>
        <link rel=\"stylesheet\" href=\"base.css\">
        
    </head>
    <body>
        {% block body %}

        {% endblock body %}
    </body>
</html>", "base.html", "/home/christophe/OneDrive/Documents/Projets/Php/KuntoManager/core/views/base.html");
    }
}
