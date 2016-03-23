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

    /**
     * Repolarda arama yapar
     *
     * @Route("github/repo-search", name="github_repo_search")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function githubSearchAction(Request $request)
    {
        $session           = $request->getSession();
        $gitHubSearchPath  = 'https://api.github.com/search/code';
        $gitHubUserPath    = 'https://api.github.com/user';
        $responseData      = array();

        if ($request->isMethod('POST')) {

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
                $responseUsername = $client->request('GET', $gitHubUserPath, array(
                    'headers' => array(
                        'Accept' => 'application/json'
                    ),
                    'query' => array(
                        'access_token' => $githubAccessToken
                    )
                ));
            } catch (\Exception $e) {
                $responseUsername = null;
            }

            if ($responseUsername === null) {
                $this->addFlash(
                    'notice',
                    'Kod yanlış veya süresi geçmiş.'
                );
                return $this->redirectToRoute('login');
            }

            $contents = $responseUsername->getBody()->getContents();
            $userInfo = json_decode($contents);

            $username = $userInfo->login;

            unset($responseUsername);
            unset($contents);

            try {
                $response = $client->request('GET', $gitHubSearchPath, array(
                    'headers' => array(
                        'Authorization' => 'bearer '. $githubAccessToken,
                        'Accept' => 'application/json'
                    ),
                    'query' => array(
                        'q' => 'user:'. $username . ' ' . $request->request->get('search_text'),
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
            $responseData['total_count'] = $data->total_count;
            $responseData['items'] = $data->items;
            $responseData['search_text'] = $request->request->get('search_text');
        }

        return $this->render('github/repo-search.html.twig', $responseData);
    }
}
