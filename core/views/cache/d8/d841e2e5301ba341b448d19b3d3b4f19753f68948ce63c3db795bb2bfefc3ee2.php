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

/* base.twig */
class __TwigTemplate_dcaac826d374596c492c9e8c0d31f7932dcfbac23e1d7e9e973efd975ab36bab extends Template
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
<html lang=\"";
        // line 2
        echo twig_escape_filter($this->env, ($context["lang"] ?? null), "html", null, true);
        echo "\" dir=\"";
        echo twig_escape_filter($this->env, ($context["dir"] ?? null), "html", null, true);
        echo "\">
    <head>
        <meta charset=\"";
        // line 4
        echo twig_escape_filter($this->env, ($context["charset"] ?? null), "html", null, true);
        echo "\">
        <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        
        <title>";
        // line 8
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFunction('env')->getCallable(), ["APP_NAME"]), "html", null, true);
        echo " - ";
        echo twig_escape_filter($this->env, ($context["title"] ?? null), "html", null, true);
        echo "</title>
        <meta name=\"description\" content=\"";
        // line 9
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFunction('env')->getCallable(), ["APP_DESCRIPTION"]), "html", null, true);
        echo "\">
        <meta name=\"author\" content=\"";
        // line 10
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFunction('env')->getCallable(), ["APP_AUTHOR_NAME"]), "html", null, true);
        echo " <";
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFunction('env')->getCallable(), ["APP_AUTHOR_EMAIL"]), "html", null, true);
        echo ">\">
        <meta name=\"copyright\" content=\"";
        // line 11
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFunction('env')->getCallable(), ["APP_COPYRIGHT"]), "html", null, true);
        echo "\">
        
        <meta name=\"generator\" content=\"";
        // line 13
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFunction('env')->getCallable(), ["APP_NAME"]), "html", null, true);
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
        <link rel=\"stylesheet\" href=\"/styles/bootstrap.min.css\">
        <link rel=\"stylesheet\" href=\"/styles/base.css\">
        
    </head>
    <body>
        ";
        // line 33
        $this->displayBlock('body', $context, $blocks);
        // line 36
        echo "    </body>
</html>";
    }

    // line 33
    public function block_body($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 34
        echo "
        ";
    }

    public function getTemplateName()
    {
        return "base.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  110 => 34,  106 => 33,  101 => 36,  99 => 33,  76 => 13,  71 => 11,  65 => 10,  61 => 9,  55 => 8,  48 => 4,  41 => 2,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<!DOCTYPE html>
<html lang=\"{{ lang }}\" dir=\"{{ dir }}\">
    <head>
        <meta charset=\"{{ charset }}\">
        <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        
        <title>{{ env('APP_NAME') }} - {{ title }}</title>
        <meta name=\"description\" content=\"{{ env('APP_DESCRIPTION') }}\">
        <meta name=\"author\" content=\"{{ env('APP_AUTHOR_NAME') }} <{{ env('APP_AUTHOR_EMAIL') }}>\">
        <meta name=\"copyright\" content=\"{{ env('APP_COPYRIGHT') }}\">
        
        <meta name=\"generator\" content=\"{{ env('APP_NAME') }}\">
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
        <link rel=\"stylesheet\" href=\"/styles/bootstrap.min.css\">
        <link rel=\"stylesheet\" href=\"/styles/base.css\">
        
    </head>
    <body>
        {% block body %}

        {% endblock body %}
    </body>
</html>", "base.twig", "/home/christophe/OneDrive/Documents/Projets/Php/KuntoManager/core/views/base.twig");
    }
}
