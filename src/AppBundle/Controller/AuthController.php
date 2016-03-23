<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;

class AuthController extends Controller
{
    /**
     * Login
     *
     * @Route("/login", name="login")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginGithubAction()
    {
        return $this->render('auth/login.html.twig');
    }

    /**
     * İzin verilen github hesabı için erişim kodunu alır ve session a yazar.
     *
     * @Route("login/check-github", name="login_check_github")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginCheckGithubAction(Request $request)
    {
        $githubClientId = $this->container->getParameter('github_client_id');
        $githubSecret   = $this->container->getParameter('github_secret');
        $code           = $request->query->get('code');
        $path           = "https://github.com/login/oauth/access_token";

        $session        = $request->getSession();
        $client         = new Client();

        $params         = array(
            'client_id' => $githubClientId,
            'client_secret' => $githubSecret,
            'code' => $code
        );

        $response = $client->request('POST', $path, array(
            'form_params' => $params,
            'headers' => array('Accept' => 'application/json')
        ));

        $contents = $response->getBody()->getContents();
        $data = json_decode($contents);

        if (isset($data->error)) {
            $this->addFlash(
                'notice',
                'Kod yanlış veya süresi geçmiş.'
            );
            return $this->redirectToRoute('login');
        }

        $session->set('github_access_token', $data->access_token);
        $session->set('github_token_type', $data->token_type);
        $session->set('github_scope', $data->scope);

        return $this->redirectToRoute('homepage');
    }
}
