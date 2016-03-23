<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;

class GithubController extends Controller
{
    /**
     * Github da bulunan repoları çeker
     * @Route("github/repos", name="github_repos")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function repoListAction(Request $request)
    {
        $session           = $request->getSession();
        $gitHubRepoPath    = 'https://api.github.com/user/repos';
        $githubAccessToken = $session->get('github_access_token');

        if (! $githubAccessToken) {
            $this->addFlash(
                'notice',
                'Giriş yapınız'
            );
            return $this->redirectToRoute('login');
        }

        $client         = new Client();

        try {
            $response = $client->request('GET', $gitHubRepoPath, array(
                'headers' => array(
                    'Authorization' => 'bearer '. $githubAccessToken,
                    'Accept' => 'application/json'
                )
            ));
        } catch (\Exception $e) {
            $response = null;
        }

        if ($response === null) {
            $this->addFlash(
                'notice',
                'Kod yanlış veya süresi geçmiş.'
            );
            return $this->redirectToRoute('login');
        }

        $contents = $response->getBody()->getContents();
        $data = json_decode($contents);

        return $this->render('github/repo-list.html.twig', array('repos' => $data));
    }
}
