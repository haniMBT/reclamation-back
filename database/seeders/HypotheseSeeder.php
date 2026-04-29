<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HypotheseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::unprepared("
        
        INSERT [dbo].[t_rcet_hypotheses_de_calcul] ([code_hypothese],[intitule_hypothese],[nature_mission],[created_at], [updated_at]) VALUES ('caractere_electrique_m2','caractere_electrique','M2',NULL,NULL) 
        INSERT [dbo].[t_rcet_hypotheses_de_calcul] ([code_hypothese],[intitule_hypothese],[nature_mission],[created_at], [updated_at]) VALUES ('factur_dutilisation_m2','factur_dutilisation','M2',NULL,NULL) 
        INSERT [dbo].[t_rcet_hypotheses_de_calcul] ([code_hypothese],[intitule_hypothese],[nature_mission],[created_at], [updated_at]) VALUES ('facteur_simultaneite_m2','facteur_simultaneite','M2',NULL,NULL) 
        INSERT [dbo].[t_rcet_hypotheses_de_calcul] ([code_hypothese],[intitule_hypothese],[nature_mission],[created_at], [updated_at]) VALUES ('chute_tension_m2','chute_tension','M2',NULL,NULL) 
        
        INSERT [dbo].[t_rcet_hypotheses_de_calcul] ([code_hypothese],[intitule_hypothese],[nature_mission],[created_at], [updated_at]) VALUES ('debit_debase_m3','debit_debase','M3',NULL,NULL) 
        INSERT [dbo].[t_rcet_hypotheses_de_calcul] ([code_hypothese],[intitule_hypothese],[nature_mission],[created_at], [updated_at]) VALUES ('simultanite_m3','simultanite','M3',NULL,NULL) 
        INSERT [dbo].[t_rcet_hypotheses_de_calcul] ([code_hypothese],[intitule_hypothese],[nature_mission],[created_at], [updated_at]) VALUES ('evacuations_m3','evacuations','M3',NULL,NULL) 
        INSERT [dbo].[t_rcet_hypotheses_de_calcul] ([code_hypothese],[intitule_hypothese],[nature_mission],[created_at], [updated_at]) VALUES ('eau_froide_sanitaire_m3','eau_froide_sanitaire','M3',NULL,NULL) 
        INSERT [dbo].[t_rcet_hypotheses_de_calcul] ([code_hypothese],[intitule_hypothese],[nature_mission],[created_at], [updated_at]) VALUES ('eau_chaude_sanitaire_m3','eau_chaude_sanitaire','M3',NULL,NULL) 
        INSERT [dbo].[t_rcet_hypotheses_de_calcul] ([code_hypothese],[intitule_hypothese],[nature_mission],[created_at], [updated_at]) VALUES ('conduit_defumee_m3','conduit_defumee','M3',NULL,NULL) 
        
        INSERT [dbo].[t_rcet_hypotheses_de_calcul] ([code_hypothese],[intitule_hypothese],[nature_mission],[created_at], [updated_at]) VALUES ('condition_base_hiver_m4','condition_base_hiver','M4',NULL,NULL) 
        INSERT [dbo].[t_rcet_hypotheses_de_calcul] ([code_hypothese],[intitule_hypothese],[nature_mission],[created_at], [updated_at]) VALUES ('condition_base_ete_m4','condition_base_ete','M4',NULL,NULL) 
        
        /*********************************************************************************************************************  */

        INSERT [dbo].[t_rcet_proposition_hypothese] ([code_proposition],[intitule_proposition],[nature_mission],[code_hypothese],[created_at], [updated_at]) VALUES ('exemple_2_1','exemple m2 1','M2','caractere_electrique_m2',NULL,NULL) 
        INSERT [dbo].[t_rcet_proposition_hypothese] ([code_proposition],[intitule_proposition],[nature_mission],[code_hypothese],[created_at], [updated_at]) VALUES ('exemple_2_2','exemple m2 2','M2','factur_dutilisation_m2',NULL,NULL) 
        INSERT [dbo].[t_rcet_proposition_hypothese] ([code_proposition],[intitule_proposition],[nature_mission],[code_hypothese],[created_at], [updated_at]) VALUES ('exemple_2_3','exemple m2 3','M2','facteur_simultaneite_m2',NULL,NULL) 
        INSERT [dbo].[t_rcet_proposition_hypothese] ([code_proposition],[intitule_proposition],[nature_mission],[code_hypothese],[created_at], [updated_at]) VALUES ('exemple_2_4','exemple m2 4','M2','chute_tension_m2',NULL,NULL) 
        INSERT [dbo].[t_rcet_proposition_hypothese] ([code_proposition],[intitule_proposition],[nature_mission],[code_hypothese],[created_at], [updated_at]) VALUES ('exemple_2_5','exemple m2 5','M2','chute_tension_m2',NULL,NULL) 
        INSERT [dbo].[t_rcet_proposition_hypothese] ([code_proposition],[intitule_proposition],[nature_mission],[code_hypothese],[created_at], [updated_at]) VALUES ('exemple_2_6','exemple m2 6','M2','chute_tension_m2',NULL,NULL) 

        INSERT [dbo].[t_rcet_proposition_hypothese] ([code_proposition],[intitule_proposition],[nature_mission],[code_hypothese],[created_at], [updated_at]) VALUES ('exemple_3_1','exemple m3 1','M3','debit_debase_m3',NULL,NULL) 
        INSERT [dbo].[t_rcet_proposition_hypothese] ([code_proposition],[intitule_proposition],[nature_mission],[code_hypothese],[created_at], [updated_at]) VALUES ('exemple_3_2','exemple m3 2','M3','simultanite_m3',NULL,NULL) 
        INSERT [dbo].[t_rcet_proposition_hypothese] ([code_proposition],[intitule_proposition],[nature_mission],[code_hypothese],[created_at], [updated_at]) VALUES ('exemple_3_3','exemple m3 3','M3','evacuations_m3',NULL,NULL) 
        INSERT [dbo].[t_rcet_proposition_hypothese] ([code_proposition],[intitule_proposition],[nature_mission],[code_hypothese],[created_at], [updated_at]) VALUES ('exemple_3_4','exemple m3 4','M3','eau_froide_sanitaire_m3',NULL,NULL) 
        INSERT [dbo].[t_rcet_proposition_hypothese] ([code_proposition],[intitule_proposition],[nature_mission],[code_hypothese],[created_at], [updated_at]) VALUES ('exemple_3_5','exemple m3 5','M3','eau_chaude_sanitaire_m3',NULL,NULL) 
        INSERT [dbo].[t_rcet_proposition_hypothese] ([code_proposition],[intitule_proposition],[nature_mission],[code_hypothese],[created_at], [updated_at]) VALUES ('exemple_3_6','exemple m3 6','M3','conduit_defumee_m3',NULL,NULL) 
        
        INSERT [dbo].[t_rcet_proposition_hypothese] ([code_proposition],[intitule_proposition],[nature_mission],[code_hypothese],[created_at], [updated_at]) VALUES ('exemple_4_1','exemple m4 1','M4','condition_base_hiver_m4',NULL,NULL) 
        INSERT [dbo].[t_rcet_proposition_hypothese] ([code_proposition],[intitule_proposition],[nature_mission],[code_hypothese],[created_at], [updated_at]) VALUES ('exemple_4_2','exemple m4 2','M4','condition_base_ete_m4',NULL,NULL) 
        
        ");
    }
}
