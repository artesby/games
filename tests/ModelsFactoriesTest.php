<?php
/**
 *
 * @author malinink
 */

class ModelsFactoriesTest extends TestCase
{
    /**
     * Test User Factory
     *
     * @return void
     */
    public function testUserFactory()
    {
        $user = factory(App\User::class)->make();
        $user->save();
        $user->delete();
    }

    /**
     * Test GameType Factory
     *
     * @return void
     */
    public function testGameTypeFactory()
    {
        $gameType = factory(App\GameType::class)->make();
        $gameType->save();
        $gameType->delete();
    }
    
    /**
     * Test User Factory
     *
     * @return void
     */
    public function testGameFactory()
    {
        $game = factory(App\Game::class)->make();
        $game->save();
        $game->delete();
        /*
         * Delete related autogenerated models also
         */
        $game->gameType()->delete();
    }
}
