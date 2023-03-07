<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemChargesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_payment_intents', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();
            $table->boolean('paid')->nullable()->default(0);
            $table->string('amount');
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('payment_method'); //wire_t
            $table->string('status',10)->nullable(); 
            $table->dateTime('due_date')->nullable(); 
            $table->json('metadata')->nullable();
            $table->morphs('customer');
            $table->timestamps();
            
            $table->foreignId('service_integration_id')
                ->constrained('service_integrations')
                ->cascadeOnDelete();            
        });

        Schema::create('system_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('identified_by');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status'); //
            $table->dateTime('trial_ends_at')->nullable();
            $table->integer('billing_cycles')->nullable()->comment('Null is forever'); 
            $table->dateTime('billing_cycle_anchor');
            $table->dateTime('current_period_start')->nullable();
            $table->dateTime('current_period_ends_at')->nullable();
            $table->dateTime('cancel_at')->nullable();
            $table->json('metadata')->nullable();
            $table->morphs('owner'); // \App\Models\User

            $table->foreignId('service_integration_id')
                ->constrained('service_integrations')
                ->cascadeOnDelete();

            $table->timestamps();
        });

        Schema::create('system_subscription_items', function (Blueprint $table) {
            $table->id();
            $table->string('quantity')->nullable()->default(1);
            $table->string('price')->nullable();
            $table->morphs('model'); // \App\Models\Programs\Program

            $table->foreignId('system_subscription_id')
                ->constrained('system_subscriptions')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('system_payment_intents', function (Blueprint $table) {
            $table->dropColumn('customer_user_id');
            $table->dropColumn('service_integration_id');

            $table->dropForeign(['customer_user_id']);            
            $table->dropForeign(['service_integration_id']);
        });

        Schema::dropIfExists('system_payment_intents');

        //
        Schema::table('system_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['service_integration_id']);
            $table->dropColumn('service_integration_id');            
        }); 

        Schema::dropIfExists('system_subscriptions');

        //
        Schema::table('system_subscription_items', function (Blueprint $table) {
            $table->dropForeign(['system_subscription_id']);
            $table->dropColumn('system_subscription_id');
        }); 

        Schema::dropIfExists('system_subscription_items');
    }
}
