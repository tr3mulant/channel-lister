<div id="{{ $container_id }}">
    <?php echo $this->buildSelectFormInput($params); ?>
    <div id="prop65-warning-type-container" class="container hidden">
        <?php echo $this->buildSelectFormInput($prop65_warning); ?>
    </div>
    <div id="prop65-chemical-name-container" class="container hidden">
        <?php echo $this->buildSelectFormInput($prop65_chem_base); ?>
    </div>
</div>